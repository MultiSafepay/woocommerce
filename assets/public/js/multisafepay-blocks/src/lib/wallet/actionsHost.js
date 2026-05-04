import {
    getBlocksCheckoutActionsContainer,
    getBlocksCheckoutMainContainer,
    getBlocksPlaceOrderButton,
    getBlocksPlaceOrderLayoutContainer,
} from '../dom/checkout';

/**
 * @file Wallet "actions host" for Checkout Blocks.
 *
 * Problem this solves:
 * - Blocks renders payment method content inside collapsible panels.
 * - Wallet buttons (Apple Pay / Google Pay) must be visible even when panels are closed.
 *
 * Approach:
 * - Create a dedicated host inside the checkout actions area.
 * - Create one slot per wallet owner so multiple wallets can coexist.
 */

/**
 * Returns the DOM id used for the single wallet actions host element.
 *
 * @returns {string}
 */
function getWalletHostId() {
    return 'msp-wallet-direct-actions-host';
}

/**
 * Returns the DOM id for the slots container within the wallet host.
 *
 * @returns {string}
 */
function getSlotsContainerId() {
    return getWalletHostId() + '-slots';
}

/**
 * Returns the DOM id for a wallet slot for a specific owner.
 *
 * @param {string} ownerId Unique wallet owner id.
 * @returns {string}
 */
function getSlotIdForOwner( ownerId ) {
    const safe = typeof ownerId === 'string' && ownerId.length ? ownerId : 'default';
    return getWalletHostId() + '-slot--' + safe;
}

/**
 * Schedules a small corrective translation for a wallet slot to keep it visually
 * constrained to the checkout actions container.
 *
 * In Checkout Blocks, the layout can settle over a few animation frames (fonts, async
 * button DOM insertion, theme CSS). This helper re-measures briefly and adjusts X
 * translation only when the slot would overflow to the right.
 *
 * @param {HTMLElement} slotEl Wallet slot element.
 * @param {HTMLElement} constraintEl Container to constrain within.
 * @returns {void}
 */
function scheduleWalletActionsVisibilityCorrection( slotEl, constraintEl ) {
    if ( ! slotEl || ! constraintEl ) {
        return;
    }

    let attempts = 0;

    const tick    = () => {
        attempts += 1;

        try {
            const constraintRect = constraintEl.getBoundingClientRect ? constraintEl.getBoundingClientRect() : null;
            const slotRectNow    = slotEl.getBoundingClientRect ? slotEl.getBoundingClientRect() : null;

            if ( ! constraintRect || ! slotRectNow ) {
                return;
            }

            const overflowRight = slotRectNow.right - constraintRect.right;

            // Accumulate correction, do not reset to 0 (avoids measurement races).
            let currentDx = 0;
            try {
                currentDx = slotEl.dataset && slotEl.dataset.mspWalletDx ? parseInt( slotEl.dataset.mspWalletDx, 10 ) : 0;
                if ( Number.isNaN( currentDx ) ) {
                    currentDx = 0;
                }
            } catch ( e ) {
                currentDx = 0;
            }

            let nextDx = currentDx;
            if ( overflowRight > 0 ) {
                nextDx -= Math.ceil( overflowRight ) + 2;
            }

            // Never push the wallet button further to the right.
            if ( nextDx > 0 ) {
                nextDx = 0;
            }

            // Clamp to a sane range.
            if ( nextDx > 2000 ) {
                nextDx = 2000;
            }
            if ( nextDx < -2000 ) {
                nextDx = -2000;
            }

            if ( nextDx !== currentDx ) {
                try {
                    slotEl.style.setProperty( 'transform', 'translateX(' + nextDx + 'px)', 'important' );
                    if ( slotEl.dataset ) {
                        slotEl.dataset.mspWalletDx = String( nextDx );
                    }
                } catch ( e ) {
                    // no-op
                }
            }

            // If we still overflow, retry a few frames (Google button can resize).
            if ( attempts < 10 && overflowRight > 1 ) {
                requestAnimationFrame( tick );
            }
        } catch ( e ) {
            // no-op
        }
    };

    try {
        requestAnimationFrame( tick );
    } catch ( e ) {
        // no-op
    }
}

/**
 * Returns true when a wallet actions slot has no real wallet button mounted.
 *
 * We intentionally ignore transient/placeholder wrappers that Blocks can render briefly
 * during re-renders, to prevent visible layout "bounce".
 *
 * @param {HTMLElement|null} slotEl
 * @returns {boolean}
 */
function isMultisafepayWalletActionsSlotEmpty( slotEl ) {
    if ( ! slotEl ) {
        return true;
    }

    // Only treat the slot as non-empty when it contains an actual wallet button.
    // Placeholder wrappers can appear briefly during Blocks re-renders; those must
    // not introduce vertical gaps.
    try {
        if ( slotEl.querySelector ) {
            const walletButton = slotEl.querySelector( '.apple-pay-button, .gpay-button, [data-msp-wallet-button="1"]' );
            if ( walletButton ) {
                return false;
            }
        }
    } catch ( e ) {
        // no-op
    }

    return true;
}

/**
 * Hides empty wallet action slots so they don't contribute vertical gaps.
 *
 * The slots container uses `row-gap`, so an empty slot being visible can push the
 * actual wallet button down briefly.
 *
 * @param {HTMLElement|null} hostEl Wallet actions host element.
 * @returns {void}
 */
function normalizeMultisafepayWalletActionsSlotsVisibility( hostEl ) {
    if ( ! hostEl ) {
        return;
    }

    try {
        const slotsContainer = hostEl.querySelector ? ( hostEl.querySelector( '#' + getSlotsContainerId() ) || hostEl ) : hostEl;
        const slots          = slotsContainer && slotsContainer.querySelectorAll
            ? slotsContainer.querySelectorAll( '.multisafepay-wallet-direct-actions-slot' )
            : [];

        for ( let i = 0, len = slots.length; i < len; i++ ) {
            const slot = slots[ i ];
            if ( ! slot || ! slot.style || typeof slot.style.setProperty !== 'function' ) {
                continue;
            }

            const empty = isMultisafepayWalletActionsSlotEmpty( slot );
            try {
                slot.style.setProperty( 'display', empty ? 'none' : 'block', 'important' );
            } catch ( e ) {
                // no-op
            }
        }
    } catch ( e ) {
        // no-op
    }
}

export function syncMultisafepayWalletActionsHostToPlaceOrder( slotEl, layoutContainer ) {
    /**
     * Syncs the wallet slot geometry with the Place order layout.
     *
     * Keeps buttons right-aligned without collapsing Google Pay width.
     *
     * @param {HTMLElement} slotEl
     * @param {HTMLElement|null} layoutContainer
     * @returns {void}
     */
    if ( ! slotEl ) {
        return;
    }

    try {
        const placeOrder = getBlocksPlaceOrderButton();
        const layoutEl   = layoutContainer || getBlocksCheckoutActionsContainer() || getBlocksPlaceOrderLayoutContainer();
        const hostId     = getWalletHostId();
        const hostEl     = document.getElementById( hostId );
        const outerHost  = hostEl && hostEl.contains( slotEl ) ? hostEl : ( slotEl && slotEl.parentNode ? slotEl.parentNode : null );
        const hostStyle  = outerHost && outerHost.style ? outerHost.style : slotEl.style;
        const slotStyle  = slotEl.style;
        const dataEl     = outerHost && outerHost.dataset ? outerHost : slotEl;

        if ( ! layoutEl ) {
            return;
        }

        const mainEl       = getBlocksCheckoutMainContainer( layoutEl );
        const constraintEl = mainEl || layoutEl;

        // Measure geometry. If the Place order is hidden (0 width), reuse the last measured values.
        // Use the checkout actions container as the canonical reference (themes can wrap the Submit button).
        const actionsEl = getBlocksCheckoutActionsContainer() || layoutEl;

        let actionsRect = null;
        let btnRect     = null;
        try {
            actionsRect = actionsEl && actionsEl.getBoundingClientRect ? actionsEl.getBoundingClientRect() : null;
        } catch ( e ) {
            // keep null
        }
        try {
            btnRect = placeOrder && placeOrder.getBoundingClientRect ? placeOrder.getBoundingClientRect() : null;
        } catch ( e ) {
            // keep null
        }

        // Capture the effective visible right boundary implied by Place order.
        // If the actions container spans under a sidebar, `actionsRect.right` can be
        // much larger than `btnRect.right`. We use that delta as a padding-right hint.
        try {
            const hasBtnRect  = ! ! ( btnRect && btnRect.width && btnRect.width > 0 );
            const safeBtnRect = hasBtnRect ? btnRect : null;
            if ( safeBtnRect && actionsRect && actionsRect.width > 0 && dataEl && dataEl.dataset ) {
                const padRightFromBtn              = Math.max( 0, Math.round( actionsRect.right - safeBtnRect.right ) );
                dataEl.dataset.mspWalletPadRightPx = String( padRightFromBtn );
            }
        } catch ( e ) {
            // no-op
        }

        // Defaults: keep the outer host full-width and align slot inside.
        try {
            hostStyle.setProperty( 'box-sizing', 'border-box', 'important' );
            hostStyle.setProperty( 'min-width', '0', 'important' );
            hostStyle.setProperty( 'width', '100%', 'important' );
            hostStyle.setProperty( 'max-width', '100%', 'important' );
            hostStyle.setProperty( 'display', 'flex', 'important' );
            hostStyle.setProperty( 'align-items', 'stretch', 'important' );
            hostStyle.setProperty( 'overflow', 'visible', 'important' );

            // Some themes use stacking contexts (e.g., sidebar overlays) that can cover
            // elements even when geometry is correct. Keep the wallet host on top.
            hostStyle.setProperty( 'position', 'relative', 'important' );
            hostStyle.setProperty( 'z-index', '20', 'important' );
            hostStyle.setProperty( 'isolation', 'isolate', 'important' );

            // Ensure the host occupies its own row in flex layouts.
            hostStyle.setProperty( 'flex', '0 0 100%', 'important' );
        } catch ( e ) {
            // no-op
        }

        try {
            slotStyle.setProperty( 'box-sizing', 'border-box', 'important' );
            slotStyle.setProperty( 'min-width', '0', 'important' );
            slotStyle.setProperty( 'overflow', 'visible', 'important' );
            slotStyle.setProperty( 'position', 'relative', 'important' );
            slotStyle.setProperty( 'z-index', '20', 'important' );
        } catch ( e ) {
            // no-op
        }

        // Width: keep the slot full-width, so Google Pay's internal button (often `width: 100%`)
        // always resolves against a definite width. Avoid shrink-to-fit here because it can
        // collapse Google Pay to ~0px in some flex layouts.
        try {
            slotStyle.setProperty( 'flex', '0 0 100%', 'important' );
            slotStyle.setProperty( 'width', '100%', 'important' );
            slotStyle.setProperty( 'max-width', '100%', 'important' );
            // Do not force display here; visibility is controlled by
            // normalizeMultisafepayWalletActionsSlotsVisibility() to avoid
            // showing transient/empty slots.
        } catch ( e ) {
            // no-op
        }

        // Alignment: always align wallet buttons to the right in the checkout actions area.
        // Do not depend on the Place order button's alignment, which varies per theme.
        try {
            hostStyle.setProperty( 'justify-content', 'flex-end', 'important' );
        } catch ( e ) {
            // no-op
        }

        // If the actions container spans under the sidebar, keep the host full-width but
        // add padding-right so right-aligned content stays within the visible column.
        try {
            const constraintRect = constraintEl && constraintEl.getBoundingClientRect ? constraintEl.getBoundingClientRect() : null;
            if ( actionsRect ) {
                const padRightFromConstraint = constraintRect
                    ? Math.max( 0, Math.round( actionsRect.right - constraintRect.right ) )
                    : 0;

                // Fallback to the Place order implied padding-right (more reliable when
                // constraintEl does not represent the visible content column in a theme).
                let padRightFromBtn = 0;
                try {
                    padRightFromBtn = dataEl.dataset && dataEl.dataset.mspWalletPadRightPx ? parseInt( dataEl.dataset.mspWalletPadRightPx, 10 ) : 0;
                    if ( Number.isNaN( padRightFromBtn ) ) {
                        padRightFromBtn = 0;
                    }
                } catch ( e ) {
                    padRightFromBtn = 0;
                }

                const effectivePadRight = Math.max( padRightFromConstraint, padRightFromBtn );

                // Reset any previous values first.
                hostStyle.setProperty( 'padding-left', '0px', 'important' );
                hostStyle.setProperty( 'padding-right', '0px', 'important' );
                hostStyle.setProperty( 'margin-left', '0px', 'important' );

                if ( effectivePadRight > 0 ) {
                    hostStyle.setProperty( 'padding-right', effectivePadRight + 'px', 'important' );
                }
            }
        } catch ( e ) {
            // no-op
        }

        // Reset any prior correction drift before applying a new correction.
        try {
            if ( slotEl && slotEl.style && typeof slotEl.style.setProperty === 'function' ) {
                slotEl.style.setProperty( 'transform', 'translateX(0px)', 'important' );
            }
            if ( slotEl && slotEl.dataset ) {
                slotEl.dataset.mspWalletDx = '0';
            }
        } catch ( e ) {
            // no-op
        }

        // Align the actual ported wallet content inside the slot.
        // The slot stays `width: 100%`, and this inner wrapper pushes the wallet button to the right
        // without forcing shrink-to-fit (which can break Google Pay).
        try {
            const inner = slotEl && slotEl.firstElementChild ? slotEl.firstElementChild : null;
            if ( inner && inner.style && typeof inner.style.setProperty === 'function' ) {
                inner.style.setProperty( 'width', '100%', 'important' );
                inner.style.setProperty( 'max-width', '100%', 'important' );
                inner.style.setProperty( 'min-width', '0', 'important' );
                inner.style.setProperty( 'display', 'flex', 'important' );
                inner.style.setProperty( 'flex-direction', 'column', 'important' );
                inner.style.setProperty( 'align-items', 'flex-end', 'important' );
                inner.style.setProperty( 'justify-content', 'flex-start', 'important' );
            }
        } catch ( e ) {
            // no-op
        }

        // Hide empty slots (e.g., the legacy/default slot) so they don't introduce
        // vertical gaps that make the wallet button "bounce" during Blocks re-renders.
        normalizeMultisafepayWalletActionsSlotsVisibility( outerHost );

        // Final safeguard: if the slot overflows the visible checkout column,
        // shift it back into view (prevents being covered by the sidebar).
        scheduleWalletActionsVisibilityCorrection( slotEl, constraintEl );
    } catch ( e ) {
        // no-op
    }
}

export function ensureMultisafepayWalletActionsHost() {
    /**
     * Ensures the host container exists in the checkout actions section.
     *
     * @returns {HTMLElement|null}
     */
    const actions = getBlocksCheckoutActionsContainer();
    if ( ! actions ) {
        return null;
    }

    const hostId = getWalletHostId();
    let host     = document.getElementById( hostId );

    const getSlotsContainerFromHost = ( hostEl ) => {
        if ( ! hostEl ) {
            return null;
        }
        const containerId = getSlotsContainerId();
        let container     = hostEl.querySelector( '#' + containerId );
        if ( container ) {
            return container;
        }

        // Backward compat: previous versions used the host itself as a slot.
        // If the host already contains an old `-slot` element, migrate it into a slots' container.
        const legacySlot    = hostEl.querySelector( '#' + hostId + '-slot' );
        container           = document.createElement( 'div' );
        container.id        = containerId;
        container.className = 'multisafepay-wallet-direct-actions-slots';
        try {
            container.style.setProperty( 'display', 'flex', 'important' );
            container.style.setProperty( 'flex-direction', 'column', 'important' );
            container.style.setProperty( 'width', '100%', 'important' );
            container.style.setProperty( 'max-width', '100%', 'important' );
            container.style.setProperty( 'row-gap', '8px', 'important' );
        } catch ( e ) {
            // no-op
        }

        try {
            // Insert the container as the only child wrapper.
            while ( hostEl.firstChild ) {
                const child = hostEl.firstChild;
                hostEl.removeChild( child );
                // Only re-attach element nodes (ignore whitespace text nodes).
                if ( child && child.nodeType === 1 ) {
                    container.appendChild( child );
                }
            }
        } catch ( e ) {
            // no-op
        }

        // If we had a legacy slot, ensure it is kept.
        if ( legacySlot && legacySlot.parentNode !== container ) {
            try {
                container.appendChild( legacySlot );
            } catch ( e ) {
                // no-op
            }
        }

        try {
            hostEl.appendChild( container );
        } catch ( e ) {
            // no-op
        }

        return container;
    };

    const ensureDefaultSlot = ( hostEl ) => {
        const slots         = getSlotsContainerFromHost( hostEl );
        if ( ! slots ) {
            return null;
        }
        const slotId = getSlotIdForOwner( 'default' );
        let slot     = slots.querySelector( '#' + slotId );
        if ( slot ) {
            return slot;
        }
        slot           = document.createElement( 'div' );
        slot.id        = slotId;
        slot.className = 'multisafepay-wallet-direct-actions-slot';
        try {
            // Keep default slot hidden unless it actually contains content.
            // Empty flex items still contribute `row-gap`, causing a visible vertical shift.
            slot.style.setProperty( 'display', 'none', 'important' );
            slot.style.setProperty( 'width', '100%', 'important' );
            slot.style.setProperty( 'max-width', '100%', 'important' );
            slot.style.setProperty( 'overflow', 'visible', 'important' );
        } catch ( e ) {
            // no-op
        }
        try {
            slots.appendChild( slot );
        } catch ( e ) {
            // no-op
        }
        return slot;
    };

    const tryInsertNearPlaceOrder = ( hostEl ) => {
        try {
            const placeOrder = actions.querySelector( 'button.wc-block-components-checkout-place-order-button' ) ||
                actions.querySelector( 'button[type="submit"]' );
            if ( ! placeOrder || ! placeOrder.parentNode || ! actions.contains( placeOrder.parentNode ) ) {
                return null;
            }

            // Always anchor directly inside the actions' container.
            // Inserting into Place order wrappers can affect flex sizing and shrink/distort
            // the Place order button in some themes/Blocks layouts.
            if ( hostEl.parentNode !== actions ) {
                try {
                    actions.appendChild( hostEl );
                } catch ( e ) {
                    // no-op
                }
            }

            try {
                if ( placeOrder.previousSibling !== hostEl && placeOrder.previousElementSibling !== hostEl ) {
                    actions.insertBefore( hostEl, placeOrder );
                }
            } catch ( e ) {
                // no-op
            }

            return actions;
        } catch ( e ) {
            return null;
        }
    };

    if ( host && actions.contains( host ) ) {
        const slot = ensureDefaultSlot( host );

        // Important: Blocks may re-render the actions DOM when switching payment methods.
        // If we early-return here without re-inserting/syncing, the host/Place order can
        // become misaligned. Always attempt to re-anchor next to Place order + re-sync.
        const layoutContainer = tryInsertNearPlaceOrder( host ) || actions;
        syncMultisafepayWalletActionsHostToPlaceOrder( slot, layoutContainer );

        return slot;
    }

    // If it exists elsewhere, remove it so we can re-attach.
    if ( host && host.parentNode ) {
        try {
            host.parentNode.removeChild( host );
        } catch ( e ) {
            // no-op
        }
    }

    host           = document.createElement( 'div' );
    host.id        = hostId;
    host.className = 'multisafepay-wallet-direct-actions';

    const slotsContainer     = document.createElement( 'div' );
    slotsContainer.id        = getSlotsContainerId();
    slotsContainer.className = 'multisafepay-wallet-direct-actions-slots';
    try {
        slotsContainer.style.setProperty( 'display', 'flex', 'important' );
        slotsContainer.style.setProperty( 'flex-direction', 'column', 'important' );
        slotsContainer.style.setProperty( 'width', '100%', 'important' );
        slotsContainer.style.setProperty( 'max-width', '100%', 'important' );
        slotsContainer.style.setProperty( 'row-gap', '8px', 'important' );
    } catch ( e ) {
        // no-op
    }

    const defaultSlot     = document.createElement( 'div' );
    defaultSlot.id        = getSlotIdForOwner( 'default' );
    defaultSlot.className = 'multisafepay-wallet-direct-actions-slot';
    try {
        // Keep default slot hidden unless it actually contains content.
        // Empty flex items still contribute `row-gap`, causing a visible vertical shift.
        defaultSlot.style.setProperty( 'display', 'none', 'important' );
        defaultSlot.style.setProperty( 'width', '100%', 'important' );
        defaultSlot.style.setProperty( 'max-width', '100%', 'important' );
        defaultSlot.style.setProperty( 'overflow', 'visible', 'important' );
    } catch ( e ) {
        // no-op
    }
    try {
        slotsContainer.appendChild( defaultSlot );
    } catch ( e ) {
        // no-op
    }

    host.appendChild( slotsContainer );

    // Try to position it where the Place order button lives.
    const anchoredContainer = tryInsertNearPlaceOrder( host );
    if ( anchoredContainer ) {
        syncMultisafepayWalletActionsHostToPlaceOrder( defaultSlot, anchoredContainer );
        return defaultSlot;
    }

    try {
        if ( typeof actions.prepend === 'function' ) {
            actions.prepend( host );
        } else {
            actions.appendChild( host );
        }
    } catch ( e ) {
        // no-op
    }

    syncMultisafepayWalletActionsHostToPlaceOrder( defaultSlot, actions );

    return defaultSlot;
}

export function ensureMultisafepayWalletActionsHostSlot( ownerId ) {
    /**
     * Ensures a dedicated slot exists for a given wallet owner.
     *
     * @param {string} ownerId
     * @returns {HTMLElement|null}
     */
    const slotOwner = typeof ownerId === 'string' && ownerId.length ? ownerId : 'default';
    const actions   = getBlocksCheckoutActionsContainer();
    if ( ! actions ) {
        return null;
    }

    // Ensure the host exists and is synced.
    ensureMultisafepayWalletActionsHost();

    const hostId = getWalletHostId();
    const host   = document.getElementById( hostId );
    if ( ! host ) {
        return null;
    }

    let slots = host.querySelector( '#' + getSlotsContainerId() );
    if ( ! slots ) {
        // Ensure a container is created/migrated.
        try {
            // Call ensure which will migrate in most cases.
            ensureMultisafepayWalletActionsHost();
            slots = host.querySelector( '#' + getSlotsContainerId() );
        } catch ( e ) {
            // no-op
        }
    }
    if ( ! slots ) {
        slots = host;
    }

    const slotId = getSlotIdForOwner( slotOwner );
    let slot     = slots.querySelector( '#' + slotId );
    if ( ! slot ) {
        slot           = document.createElement( 'div' );
        slot.id        = slotId;
        slot.className = 'multisafepay-wallet-direct-actions-slot';
        try {
            // Keep the slot hidden until it contains a real wallet button.
            // This prevents placeholder nodes from adding vertical gaps.
            slot.style.setProperty( 'display', 'none', 'important' );
            slot.style.setProperty( 'width', '100%', 'important' );
            slot.style.setProperty( 'max-width', '100%', 'important' );
            slot.style.setProperty( 'overflow', 'visible', 'important' );
        } catch ( e ) {
            // no-op
        }
        try {
            slots.appendChild( slot );
        } catch ( e ) {
            // no-op
        }
    }

    syncMultisafepayWalletActionsHostToPlaceOrder( slot, actions );
    return slot;
}

function cleanupMultisafepayWalletActionsHostIfEmpty() {
    const hostId = getWalletHostId();
    const host   = document.getElementById( hostId );
    if ( ! host ) {
        return false;
    }

    const slotsContainer = host.querySelector( '#' + getSlotsContainerId() ) || host;

    const slotSelector = '.multisafepay-wallet-direct-actions-slot';
    const isSlotEmpty  = ( slotEl ) => {
        if ( ! slotEl ) {
            return true;
        }
        try {
            if ( slotEl.querySelector && slotEl.querySelector( '*' ) ) {
                return false;
            }
        } catch ( e ) {
            // no-op
        }

        try {
            const text = typeof slotEl.textContent === 'string' ? slotEl.textContent.trim() : '';
            return ! text;
        } catch ( e ) {
            return true;
        }
    };

    // Remove empty slot wrappers so they don't prevent host cleanup.
    try {
        const slots = slotsContainer && slotsContainer.querySelectorAll ? slotsContainer.querySelectorAll( slotSelector ) : [];
        slots.forEach(
            ( slotEl ) => {
                if ( isSlotEmpty( slotEl ) ) {
                    try {
                        slotsContainer.removeChild( slotEl );
                    } catch ( e ) {
                        // no-op
                    }
                }
            }
        );
    } catch ( e ) {
        // no-op
    }

    // If any slot still contains elements/text, do not remove the host.
    try {
        const slots = slotsContainer && slotsContainer.querySelectorAll ? slotsContainer.querySelectorAll( slotSelector ) : [];
        for ( let i = 0, len = slots.length; i < len; i++ ) {
            if ( ! isSlotEmpty( slots[ i ] ) ) {
                return false;
            }
        }
    } catch ( e ) {
        // no-op
    }

    try {
        if ( host.parentNode ) {
            host.parentNode.removeChild( host );
        }
        return true;
    } catch ( e ) {
        return false;
    }
}

export function cleanupMultisafepayWalletActionsHostIfEmptyForOwner( expectedOwnerId ) {
    /**
     * Removes a wallet slot (and possibly the host) if it is empty.
     *
     * @param {string} expectedOwnerId
     * @returns {void}
     */
    // Remove the slot for this owner if it's empty.
    try {
        const hostId = getWalletHostId();
        const host   = document.getElementById( hostId );
        if ( host ) {
            const slotsContainer = host.querySelector( '#' + getSlotsContainerId() ) || host;
            const slotId         = getSlotIdForOwner( expectedOwnerId || 'default' );
            const slot           = slotsContainer.querySelector( '#' + slotId );
            if ( slot ) {
                let hasElements = false;
                try {
                    hasElements = ! ! ( slot.querySelector && slot.querySelector( '*' ) );
                } catch ( e ) {
                    // keep false
                }

                let hasText = false;
                try {
                    const text = typeof slot.textContent === 'string' ? slot.textContent.trim() : '';
                    hasText    = ! ! text;
                } catch ( e ) {
                    // keep false
                }

                if ( ! hasElements && ! hasText ) {
                    try {
                        slotsContainer.removeChild( slot );
                    } catch ( e ) {
                        // no-op
                    }
                }
            }
        }
    } catch ( e ) {
        // no-op
    }

    return cleanupMultisafepayWalletActionsHostIfEmpty();
}

export function hasMultisafepayWalletActionsHostAnyContent() {
    /**
     * Checks if any wallet slot currently contains content.
     *
     * Used to decide whether to hide the Place order button.
     *
     * @returns {boolean}
     */
    try {
        const host = document.getElementById( getWalletHostId() );
        if ( ! host ) {
            return false;
        }
        const slotsContainer = host.querySelector( '#' + getSlotsContainerId() ) || host;

        const slots = slotsContainer && slotsContainer.querySelectorAll
            ? slotsContainer.querySelectorAll( '.multisafepay-wallet-direct-actions-slot' )
            : [];

        for ( let i = 0, len = slots.length; i < len; i++ ) {
            const slot = slots[ i ];
            try {
                if ( slot && slot.querySelector && slot.querySelector( '*' ) ) {
                    return true;
                }
            } catch ( e ) {
                // no-op
            }

            try {
                const text = typeof slot.textContent === 'string' ? slot.textContent.trim() : '';
                if ( text ) {
                    return true;
                }
            } catch ( e ) {
                // no-op
            }
        }

        return false;
    } catch ( e ) {
        return false;
    }
}
