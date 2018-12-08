/* global menus, ajaxurl */
jQuery( document ).ready( function( $ ) {
	$( '.menu-item-depth-0 .is-submenu' ).hide();
	$( '#submit-wechatlinkdiv' ).on( 'click', function() {
		var processMethod = function( menuMarkup ) { 
				var $menuMarkup = $( menuMarkup );

				$menuMarkup.hideAdvancedMenuItemFields().appendTo( $( '#menu-to-edit' ) );
				refreshKeyboardAccessibility();
				refreshAdvancedAccessibility();
				$( document ).trigger( 'menu-item-added', [ $menuMarkup ] );
			},
			refreshKeyboardAccessibility = function() {
				$( 'a.item-edit' ).off( 'focus' ).on( 'focus', function() {
					$( this ).off( 'keydown' ).on( 'keydown', function( e ) {

						var arrows,
							$this = $( this ),
							thisItem = $this.parents( 'li.menu-item' ),
							thisItemData = thisItem.getItemData(),
							maybeArrow = parseInt( e.which, 10 );

						// Bail if it's not an arrow key
						if ( 37 !== maybeArrow && 38 !== maybeArrow && 39 !== maybeArrow && 40 !== maybeArrow ) {

							return;
						}

						// Avoid multiple keydown events
						$this.off( 'keydown' );

						// Bail if there is only one menu item
						if ( 1 === $( '#menu-to-edit li' ).length ) {

							return;
						}

						// If RTL, swap left/right arrows
						arrows = { '38': 'up', '40': 'down', '37': 'left', '39': 'right' };
						if ( $( 'body' ).hasClass( 'rtl' ) ) {
							arrows = { '38' : 'up', '40' : 'down', '39' : 'left', '37' : 'right' };
						}

						switch ( arrows[e.which] ) {
						case 'up':
							moveMenuItem( $this, 'up' );
							break;
						case 'down':
							moveMenuItem( $this, 'down' );
							break;
						case 'left':
							moveMenuItem( $this, 'left' );
							break;
						case 'right':
							moveMenuItem( $this, 'right' );
							break;
						}
						// Put focus back on same menu item
						$( '#edit-' + thisItemData['menu-item-db-id'] ).focus();
						return false;
					} );
				} );
			},
			refreshAdvancedAccessibility = function() {
				// Hide all the move buttons by default.
				$( '.menu-item-settings .field-move .menus-move' ).hide();

				// Mark all menu items as unprocessed
				$( 'a.item-edit' ).data( 'needs_accessibility_refresh', true );

				// All open items have to be refreshed or they will show no links
				$( '.menu-item-edit-active a.item-edit' ).each( function() {
					refreshAdvancedAccessibilityOfItem( this );
				} );
			},
			refreshAdvancedAccessibilityOfItem = function( itemToRefresh ) {
				// Only refresh accessibility when necessary
				if ( true !== $( itemToRefresh ).data( 'needs_accessibility_refresh' ) ) {
					return;
				}

				var thisLink, thisLinkText, primaryItems, itemPosition, title,
					parentItem, parentItemId, parentItemName, subItems,
					$this = $( itemToRefresh ),
					menuItem = $this.closest( 'li.menu-item' ).first(),
					depth = menuItem.menuItemDepth(),
					isPrimaryMenuItem = ( 0 === depth ),
					itemName = $this.closest( '.menu-item-handle' ).find( '.menu-item-title' ).text(),
					position = parseInt( menuItem.index(), 10 ),
					prevItemDepth = ( isPrimaryMenuItem ) ? depth : parseInt( depth - 1, 10 ),
					prevItemNameLeft = menuItem.prevAll( '.menu-item-depth-' + prevItemDepth ).first().find( '.menu-item-title' ).text(),
					prevItemNameRight = menuItem.prevAll( '.menu-item-depth-' + depth ).first().find( '.menu-item-title' ).text(),
					totalMenuItems = $( '#menu-to-edit li' ).length,
					hasSameDepthSibling = menuItem.nextAll( '.menu-item-depth-' + depth ).length;

					menuItem.find( '.field-move' ).toggle( totalMenuItems > 1 );

				// Where can they move this menu item?
				if ( 0 !== position ) {
					thisLink = menuItem.find( '.menus-move-up' );
					thisLink.attr( 'aria-label', menus.moveUp ).css( 'display', 'inline' );
				}

				if ( 0 !== position && isPrimaryMenuItem ) {
					thisLink = menuItem.find( '.menus-move-top' );
					thisLink.attr( 'aria-label', menus.moveToTop ).css( 'display', 'inline' );
				}

				if ( position + 1 !== totalMenuItems && 0 !== position ) {
					thisLink = menuItem.find( '.menus-move-down' );
					thisLink.attr( 'aria-label', menus.moveDown ).css( 'display', 'inline' );
				}

				if ( 0 === position && 0 !== hasSameDepthSibling ) {
					thisLink = menuItem.find( '.menus-move-down' );
					thisLink.attr( 'aria-label', menus.moveDown ).css( 'display', 'inline' );
				}

				if ( ! isPrimaryMenuItem ) {
					thisLink = menuItem.find( '.menus-move-left' ),
					thisLinkText = menus.outFrom.replace( '%s', prevItemNameLeft );
					thisLink.attr( 'aria-label', menus.moveOutFrom.replace( '%s', prevItemNameLeft ) ).text( thisLinkText ).css( 'display', 'inline' );
				}

				if ( 0 !== position ) {
					if ( menuItem.find( '.menu-item-data-parent-id' ).val() !== menuItem.prev().find( '.menu-item-data-db-id' ).val() ) {
						thisLink = menuItem.find( '.menus-move-right' ),
						thisLinkText = menus.under.replace( '%s', prevItemNameRight );
						thisLink.attr( 'aria-label', menus.moveUnder.replace( '%s', prevItemNameRight ) ).text( thisLinkText ).css( 'display', 'inline' );
					}
				}

				if ( isPrimaryMenuItem ) {
					primaryItems = $( '.menu-item-depth-0' ),
					itemPosition = primaryItems.index( menuItem ) + 1,
					totalMenuItems = primaryItems.length,

					// String together help text for primary menu items
					title = menus.menuFocus.replace( '%1$s', itemName ).replace( '%2$d', itemPosition ).replace( '%3$d', totalMenuItems );
				} else {
					parentItem = menuItem.prevAll( '.menu-item-depth-' + parseInt( depth - 1, 10 ) ).first(),
					parentItemId = parentItem.find( '.menu-item-data-db-id' ).val(),
					parentItemName = parentItem.find( '.menu-item-title' ).text(),
					subItems = $( '.menu-item .menu-item-data-parent-id[value="' + parentItemId + '"]' ),
					itemPosition = $( subItems.parents( '.menu-item' ).get().reverse() ).index( menuItem ) + 1;

					// String together help text for sub menu items
					title = menus.subMenuFocus.replace( '%1$s', itemName ).replace( '%2$d', itemPosition ).replace( '%3$s', parentItemName );
				}

				// @todo Consider to update just the `aria-label` attribute.
				$this.attr( 'aria-label', title ).text( title );

				// Mark this item's accessibility as refreshed
				$this.data( 'needs_accessibility_refresh', false );
			},
			moveMenuItem = function( $this, dir ) {

				var items, newItemPosition, newDepth,
					menuItems = $( '#menu-to-edit li' ),
					menuItemsCount = menuItems.length,
					thisItem = $this.parents( 'li.menu-item' ),
					thisItemChildren = thisItem.childMenuItems(),
					thisItemData = thisItem.getItemData(),
					thisItemDepth = parseInt( thisItem.menuItemDepth(), 10 ),
					thisItemPosition = parseInt( thisItem.index(), 10 ),
					nextItem = thisItem.next(),
					nextItemChildren = nextItem.childMenuItems(),
					nextItemDepth = parseInt( nextItem.menuItemDepth(), 10 ) + 1,
					prevItem = thisItem.prev(),
					prevItemDepth = parseInt( prevItem.menuItemDepth(), 10 ),
					prevItemId = prevItem.getItemData()['menu-item-db-id'];

				switch ( dir ) {
				case 'up':
					newItemPosition = thisItemPosition - 1;

					// Already at top
					if ( 0 === thisItemPosition ) {
						break;
					}

					// If a sub item is moved to top, shift it to 0 depth
					if ( 0 === newItemPosition && 0 !== thisItemDepth ) {
						thisItem.moveHorizontally( 0, thisItemDepth );
					}

					// If prev item is sub item, shift to match depth
					if ( 0 !== prevItemDepth ) {
						thisItem.moveHorizontally( prevItemDepth, thisItemDepth );
					}

					// Does this item have sub items?
					if ( thisItemChildren ) {
						items = thisItem.add( thisItemChildren );
						// Move the entire block
						items.detach().insertBefore( menuItems.eq( newItemPosition ) ).updateParentMenuItemDBId();
					} else {
						thisItem.detach().insertBefore( menuItems.eq( newItemPosition ) ).updateParentMenuItemDBId();
					}
					break;
				case 'down':
					// Does this item have sub items?
					if ( thisItemChildren ) {
						items = thisItem.add( thisItemChildren ),
							nextItem = menuItems.eq( items.length + thisItemPosition ),
							nextItemChildren = 0 !== nextItem.childMenuItems().length;

						if ( nextItemChildren ) {
							newDepth = parseInt( nextItem.menuItemDepth(), 10 ) + 1;
							thisItem.moveHorizontally( newDepth, thisItemDepth );
						}

						// Have we reached the bottom?
						if ( menuItemsCount === thisItemPosition + items.length ) {
							break;
						}

						items.detach().insertAfter( menuItems.eq( thisItemPosition + items.length ) ).updateParentMenuItemDBId();
					} else {
						// If next item has sub items, shift depth
						if ( 0 !== nextItemChildren.length ) {
							thisItem.moveHorizontally( nextItemDepth, thisItemDepth );
						}

						// Have we reached the bottom
						if ( menuItemsCount === thisItemPosition + 1 ) {
							break;
						}
						thisItem.detach().insertAfter( menuItems.eq( thisItemPosition + 1 ) ).updateParentMenuItemDBId();
					}
					break;
				case 'top':
					// Already at top
					if ( 0 === thisItemPosition ) {
						break;
					}
					// Does this item have sub items?
					if ( thisItemChildren ) {
						items = thisItem.add( thisItemChildren );
						// Move the entire block
						items.detach().insertBefore( menuItems.eq( 0 ) ).updateParentMenuItemDBId();
					} else {
						thisItem.detach().insertBefore( menuItems.eq( 0 ) ).updateParentMenuItemDBId();
					}
					break;
				case 'left':
					// As far left as possible
					if ( 0 === thisItemDepth ) {
						break;
					}
					thisItem.shiftHorizontally( -1 );
					break;
				case 'right':
					// Can't be sub item at top
					if ( 0 === thisItemPosition ) {
						break;
					}
					// Already sub item of prevItem
					if ( thisItemData['menu-item-parent-id'] === prevItemId ) {
						break;
					}
					thisItem.shiftHorizontally( 1 );
					break;
				}
				$this.focus();
				refreshKeyboardAccessibility();
				refreshAdvancedAccessibility();
			},
			callback = function() {
				$( '.wechatlinkdiv .spinner' ).removeClass( 'is-active' );
				$( '#wechat-menu-item-name' ).val( '' ).blur();
				$( '#wechat-menu-item-url' ).val( '' );
			},
			url 		= $( '#wechat-menu-item-url' ).val(),
			label 		= $( '#wechat-menu-item-name' ).val(),
			target 		= $( '#wechat-menu-item-attr-title' ).val(),
			menu 		= $( '#menu' ).val(),
			nonce 		= $( '#menu-settings-column-nonce' ).val(),
			menuItem 	= {
				'-1': {
					'menu-item-type': 'wechat',
					'menu-item-url': url,
					'menu-item-title': label,
					'menu-item-attr-title': target
				}
			},
			params 		= {
				'action': 'add_wechat_menu_item',
				'menu': menu,
				'menu-settings-column-nonce': nonce,
				'menu-item': menuItem
			};

		$( '.wechatlinkdiv .spinner' ).addClass( 'is-active' );
		$.post( ajaxurl, params, function( menuMarkup ) {
			var ins = $( '#menu-instructions' );

			menuMarkup = $.trim( menuMarkup );
			processMethod( menuMarkup, params );

			$( 'li.pending' ).hide().fadeIn( 'slow' );
			$( '.drag-instructions' ).show();
			if( ! ins.hasClass( 'menu-instructions-inactive' ) && ins.siblings().length ) {
				ins.addClass( 'menu-instructions-inactive' );
			}

			callback();
		} );
	} );
} );