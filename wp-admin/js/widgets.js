/*global ajaxurl, isRtl */
var wpWidgets;
(function($) {
    var $document = $( document ); 

wpWidgets = {



	init : function() {
		var rem, the_id,
			self = this,
			chooser = $('.widgets-chooser'),
			selectSidebar = chooser.find('.widgets-chooser-sidebars'),
			sidebars = $('div.widgets-sortables'),
			isRTL = !! ( 'undefined' !== typeof isRtl && isRtl );

		$('#widgets-right .sidebar-name').click( function() {
			var $this = $(this),
				$wrap = $this.closest('.widgets-holder-wrap');

			if ( $wrap.hasClass('closed') ) {
				$wrap.removeClass('closed');
				$this.parent().sortable('refresh');
			} else {
				$wrap.addClass('closed');
			}
            $document.triggerHandler( 'wp-pin-menu' ); 
		});

		$('#widgets-left .sidebar-name').click( function() {
			$(this).closest('.widgets-holder-wrap').toggleClass('closed');
            $document.triggerHandler( 'wp-pin-menu' ); 
		});

		$(document.body).bind('click.widgets-toggle', function(e) {
			var target = $(e.target),
				css = { 'z-index': 100 },
				widget, inside, targetWidth, widgetWidth, margin;

			if ( target.parents('.widget-top').length && ! target.parents('#available-widgets').length ) {
				widget = target.closest('div.widget');
				inside = widget.children('.widget-inside');
				targetWidth = parseInt( widget.find('input.widget-width').val(), 10 ),
				widgetWidth = widget.parent().width();

				if ( inside.is(':hidden') ) {
					if ( targetWidth > 250 && ( targetWidth + 30 > widgetWidth ) && widget.closest('div.widgets-sortables').length ) {
						if ( widget.closest('div.widget-liquid-right').length ) {
							margin = isRTL ? 'margin-right' : 'margin-left';
						} else {
							margin = isRTL ? 'margin-left' : 'margin-right';
						}

						css[ margin ] = widgetWidth - ( targetWidth + 30 ) + 'px';
						widget.css( css );
					}
					widget.addClass( 'open' );
					inside.slideDown('fast');
				} else {
					inside.slideUp('fast', function() {
						widget.attr( 'style', '' );
						widget.removeClass( 'open' );
					});
				}
				e.preventDefault();
			} else if ( target.hasClass('widget-control-save') ) {
				wpWidgets.save( target.closest('div.widget'), 0, 1, 0 );
				e.preventDefault();
			} else if ( target.hasClass('widget-control-remove') ) {
				wpWidgets.save( target.closest('div.widget'), 1, 1, 0 );
				e.preventDefault();
			} else if ( target.hasClass('widget-control-close') ) {
				widget = target.closest('div.widget');
				widget.removeClass( 'open' );
				wpWidgets.close( widget );
				e.preventDefault();
			}
		});

		sidebars.children('.widget').each( function() {
			var $this = $(this);

			wpWidgets.appendTitle( this );

			if ( $this.find( 'p.widget-error' ).length ) {
				$this.find( 'a.widget-action' ).trigger('click');
			}
		});

		$('#widget-list').children('.widget').draggable({
			connectToSortable: 'div.widgets-sortables',
			handle: '> .widget-top > .widget-title',
			distance: 2,
			helper: 'clone',
			zIndex: 100,
			containment: '#wpwrap',
			start: function( event, ui ) {
				var chooser = $(this).find('.widgets-chooser');

				ui.helper.find('div.widget-description').hide();
				the_id = this.id;

				if ( chooser.length ) {
					// Hide the chooser and move it out of the widget
					$( '#wpbody-content' ).append( chooser.hide() );
					// Delete the cloned chooser from the drag helper
					ui.helper.find('.widgets-chooser').remove();
					self.clearWidgetSelection();
				}
			},
			stop: function() {
				if ( rem ) {
					$(rem).hide();
				}

				rem = '';
			}
		});

		sidebars.sortable({
			placeholder: 'widget-placeholder',
			items: '> .widget',
			handle: '> .widget-top > .widget-title',
			cursor: 'move',
			distance: 2,
			containment: '#wpwrap',
			start: function( event, ui ) {
				var height, $this = $(this),
					$wrap = $this.parent(),
					inside = ui.item.children('.widget-inside');

				if ( inside.css('display') === 'block' ) {
					inside.hide();
					$(this).sortable('refreshPositions');
				}

				if ( ! $wrap.hasClass('closed') ) {
					// Lock all open sidebars min-height when starting to drag.
					// Prevents jumping when dragging a widget from an open sidebar to a closed sidebar below.
					height = ui.item.hasClass('ui-draggable') ? $this.height() : 1 + $this.height();
					$this.css( 'min-height', height + 'px' );
				}
			},

			stop: function( event, ui ) {
				var addNew, widgetNumber, $sidebar, $children, child, item,
					$widget = ui.item,
					id = the_id;

				if ( $widget.hasClass('deleting') ) {
					wpWidgets.save( $widget, 1, 0, 1 ); // delete widget
					$widget.remove();
					return;
				}

				addNew = $widget.find('input.add_new').val();
				widgetNumber = $widget.find('input.multi_number').val();

				$widget.attr( 'style', '' ).removeClass('ui-draggable');
				the_id = '';

				if ( addNew ) {
					if ( 'multi' === addNew ) {
						$widget.html(
							$widget.html().replace( /<[^<>]+>/g, function( tag ) {
								return tag.replace( /__i__|%i%/g, widgetNumber );
							})
						);

						$widget.attr( 'id', id.replace( '__i__', widgetNumber ) );
						widgetNumber++;

						$( 'div#' + id ).find( 'input.multi_number' ).val( widgetNumber );
					} else if ( 'single' === addNew ) {
						$widget.attr( 'id', 'new-' + id );
						rem = 'div#' + id;
					}

					wpWidgets.save( $widget, 0, 0, 1 );
					$widget.find('input.add_new').val('');
					$document.trigger( 'widget-added', [ $widget ] ); 
				}

				$sidebar = $widget.parent();

				if ( $sidebar.parent().hasClass('closed') ) {
					$sidebar.parent().removeClass('closed');
					$children = $sidebar.children('.widget');

					// Make sure the dropped widget is at the top
					if ( $children.length > 1 ) {
						child = $children.get(0);
						item = $widget.get(0);

						if ( child.id && item.id && child.id !== item.id ) {
							$( child ).before( $widget );
						}
					}
				}

				if ( addNew ) {
					$widget.find( 'a.widget-action' ).trigger('click');
				} else {
					wpWidgets.saveOrder( $sidebar.attr('id') );
				}
			},

			activate: function() {
				$(this).parent().addClass( 'widget-hover' );
			},

			deactivate: function() {
				// Remove all min-height added on "start"
				$(this).css( 'min-height', '' ).parent().removeClass( 'widget-hover' );
			},

			receive: function( event, ui ) {
				var $sender = $( ui.sender );

				// Don't add more widgets to orphaned sidebars
				if ( this.id.indexOf('orphaned_widgets') > -1 ) {
					$sender.sortable('cancel');
					return;
				}

				// If the last widget was moved out of an orphaned sidebar, close and remove it.
				if ( $sender.attr('id').indexOf('orphaned_widgets') > -1 && ! $sender.children('.widget').length ) {
					$sender.parents('.orphan-sidebar').slideUp( 400, function(){ $(this).remove(); } );
				}
			}
		}).sortable( 'option', 'connectWith', 'div.widgets-sortables' );

		$('#available-widgets').droppable({
			tolerance: 'pointer',
			accept: function(o){
				return $(o).parent().attr('id') !== 'widget-list';
			},
			drop: function(e,ui) {
				ui.draggable.addClass('deleting');
				$('#removing-widget').hide().children('span').empty();
			},
			over: function(e,ui) {
				ui.draggable.addClass('deleting');
				$('div.widget-placeholder').hide();

				if ( ui.draggable.hasClass('ui-sortable-helper') ) {
					$('#removing-widget').show().children('span')
					.html( ui.draggable.find('div.widget-title').children('h4').html() );
				}
			},
			out: function(e,ui) {
				ui.draggable.removeClass('deleting');
				$('div.widget-placeholder').show();
				$('#removing-widget').hide().children('span').empty();
			}
		});

		// Area Chooser
		$( '#widgets-right .widgets-holder-wrap' ).each( function( index, element ) {
			var $element = $( element ),
				name = $element.find( '.sidebar-name h3' ).text(),
				id = $element.find( '.widgets-sortables' ).attr( 'id' ),
				li = $('<li tabindex="0">').text( $.trim( name ) );

			if ( index === 0 ) {
				li.addClass( 'widgets-chooser-selected' );
			}

			selectSidebar.append( li );
			li.data( 'sidebarId', id );
		});

		$( '#available-widgets .widget .widget-title' ).on( 'click.widgets-chooser', function() {
			var $widget = $(this).closest( '.widget' );

			if ( $widget.hasClass( 'widget-in-question' ) || $( '#widgets-left' ).hasClass( 'chooser' ) ) {
				self.closeChooser();
			} else {
				// Open the chooser
				self.clearWidgetSelection();
				$( '#widgets-left' ).addClass( 'chooser' );
				$widget.addClass( 'widget-in-question' ).children( '.widget-description' ).after( chooser );

				chooser.slideDown( 300, function() {
					selectSidebar.find('.widgets-chooser-selected').focus();
				});

				selectSidebar.find( 'li' ).on( 'focusin.widgets-chooser', function() {
					selectSidebar.find('.widgets-chooser-selected').removeClass( 'widgets-chooser-selected' );
					$(this).addClass( 'widgets-chooser-selected' );
				} );
			}
		});

		// Add event handlers
		chooser.on( 'click.widgets-chooser', function( event ) {
			var $target = $( event.target );

			if ( $target.hasClass('button-primary') ) {
				self.addWidget( chooser );
				self.closeChooser();
			} else if ( $target.hasClass('button-secondary') ) {
				self.closeChooser();
			}
		}).on( 'keyup.widgets-chooser', function( event ) {
			if ( event.which === $.ui.keyCode.ENTER ) {
				if ( $( event.target ).hasClass('button-secondary') ) {
					// Close instead of adding when pressing Enter on the Cancel button
					self.closeChooser();
				} else {
					self.addWidget( chooser );
					self.closeChooser();
				}
			} else if ( event.which === $.ui.keyCode.ESCAPE ) {
				self.closeChooser();
			}
		});
	},

	saveOrder : function( sidebarId ) {
		var data = {
			action: 'widgets-order',
			savewidgets: $('#_wpnonce_widgets').val(),
			sidebars: []
		};

		if ( sidebarId ) {
			$( '#' + sidebarId ).find( '.spinner:first' ).addClass( 'is-active' );
		}

		$('div.widgets-sortables').each( function() {
			if ( $(this).sortable ) {
				data['sidebars[' + $(this).attr('id') + ']'] = $(this).sortable('toArray').join(',');
			}
		});

		$.post( ajaxurl, data, function() {
			$( '.spinner' ).removeClass( 'is-active' );
		});
	},

	save : function( widget, del, animate, order ) {
		var sidebarId = widget.closest('div.widgets-sortables').attr('id'),
			data = widget.find('form').serialize(), a;

		widget = $(widget);
		$( '.spinner', widget ).addClass( 'is-active' );

		a = {
			action: 'save-widget',
			savewidgets: $('#_wpnonce_widgets').val(),
			sidebar: sidebarId
		};

		if ( del ) {
			a.delete_widget = 1;
		}

		data += '&' + $.param(a);

		$.post( ajaxurl, data, function(r) {
			var id;

			if ( del ) {
				if ( ! $('input.widget_number', widget).val() ) {
					id = $('input.widget-id', widget).val();
					$('#available-widgets').find('input.widget-id').each(function(){
						if ( $(this).val() === id ) {
							$(this).closest('div.widget').show();
						}
					});
				}

				if ( animate ) {
					order = 0;
					widget.slideUp('fast', function(){
						$(this).remove();
						wpWidgets.saveOrder();
					});
				} else {
					widget.remove();
				}
			} else {
				$( '.spinner' ).removeClass( 'is-active' );
				if ( r && r.length > 2 ) {
					$( 'div.widget-content', widget ).html( r );
					wpWidgets.appendTitle( widget );
                    $document.trigger( 'widget-updated', [ widget ] ); 
				}
			}
			if ( order ) {
				wpWidgets.saveOrder();
			}
		});
	},

	appendTitle : function(widget) {
		var title = $('input[id*="-title"]', widget).val() || '';

		if ( title ) {
			title = ': ' + title.replace(/<[^<>]+>/g, '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
		}

		$(widget).children('.widget-top').children('.widget-title').children()
				.children('.in-widget-title').html(title);

	},

	close : function(widget) {
		widget.children('.widget-inside').slideUp('fast', function() {
			widget.attr( 'style', '' );
		});
	},

	addWidget: function( chooser ) {
		var widget, widgetId, add, n, viewportTop, viewportBottom, sidebarBounds,
			sidebarId = chooser.find( '.widgets-chooser-selected' ).data('sidebarId'),
			sidebar = $( '#' + sidebarId );

		widget = $('#available-widgets').find('.widget-in-question').clone();
		widgetId = widget.attr('id');
		add = widget.find( 'input.add_new' ).val();
		n = widget.find( 'input.multi_number' ).val();

		// Remove the cloned chooser from the widget
		widget.find('.widgets-chooser').remove();

		if ( 'multi' === add ) {
			widget.html(
				widget.html().replace( /<[^<>]+>/g, function(m) {
					return m.replace( /__i__|%i%/g, n );
				})
			);

			widget.attr( 'id', widgetId.replace( '__i__', n ) );
			n++;
			$( '#' + widgetId ).find('input.multi_number').val(n);
		} else if ( 'single' === add ) {
			widget.attr( 'id', 'new-' + widgetId );
			$( '#' + widgetId ).hide();
		}

		// Open the widgets container
		sidebar.closest( '.widgets-holder-wrap' ).removeClass('closed');

		sidebar.append( widget );
		sidebar.sortable('refresh');

		wpWidgets.save( widget, 0, 0, 1 );
		// No longer "new" widget
		widget.find( 'input.add_new' ).val('');

        $document.trigger( 'widget-added', [ widget ] ); 

		/*
		 * Check if any part of the sidebar is visible in the viewport. If it is, don't scroll.
		 * Otherwise, scroll up to so the sidebar is in view.
		 *
		 * We do this by comparing the top and bottom, of the sidebar so see if they are within
		 * the bounds of the viewport.
		 */
		viewportTop = $(window).scrollTop();
		viewportBottom = viewportTop + $(window).height();
		sidebarBounds = sidebar.offset();

		sidebarBounds.bottom = sidebarBounds.top + sidebar.outerHeight();

		if ( viewportTop > sidebarBounds.bottom || viewportBottom < sidebarBounds.top ) {
			$( 'html, body' ).animate({
				scrollTop: sidebarBounds.top - 130
			}, 200 );
		}

		window.setTimeout( function() {
			// Cannot use a callback in the animation above as it fires twice,
			// have to queue this "by hand".
			widget.find( '.widget-title' ).trigger('click');
		}, 250 );
	},

	closeChooser: function() {
		var self = this;

		$( '.widgets-chooser' ).slideUp( 200, function() {
			$( '#wpbody-content' ).append( this );
			self.clearWidgetSelection();
		});
	},

	clearWidgetSelection: function() {
		$( '#widgets-left' ).removeClass( 'chooser' );
		$( '.widget-in-question' ).removeClass( 'widget-in-question' );
	}
};


$document.ready( function(){ wpWidgets.init(); } ); 

	/* ____________________________________ BANNER WIDGET ___________________________________ */
	wpBannerWidget = {

		// Call this from the upload button to initiate the upload frame.
		uploader : function( widget_id, widget_id_string ) {

			var frame = wp.media({
				title : 'Select an Image',
				multiple : false,
				library : { type : 'image' },
				button : { text : 'Insert Into Widget' }
			});

			// Handle results from media manager.
			frame.on('close',function( ) {
				var attachments = frame.state().get('selection').toJSON();
				wpBannerWidget.render( widget_id, widget_id_string, attachments[0] );
			});

			frame.open();
			return false;
		},

		// Output Image preview and populate widget form.
		render : function( widget_id, widget_id_string, attachment ) {
			if(null == attachment) return ;


			$("#" + widget_id_string + 'preview').html(wpBannerWidget.imgHTML( attachment ));

			$("#" + widget_id_string + 'fields').slideDown();

			$("#" + widget_id_string + 'attachment_id').val(attachment.id);
			$("#" + widget_id_string + 'imageurl').val(attachment.url);
			$("#" + widget_id_string + 'aspect_ratio').val(attachment.width/attachment.height);
			$("#" + widget_id_string + 'width').val(attachment.width);
			$("#" + widget_id_string + 'height').val(attachment.height);

			$("#" + widget_id_string + 'size').val('full');
			$("#" + widget_id_string + 'custom_size_selector').slideDown();
			wpBannerWidget.toggleSizes( widget_id, widget_id_string );

			wpBannerWidget.updateInputIfEmpty( widget_id_string, 'title', attachment.title );
			wpBannerWidget.updateInputIfEmpty( widget_id_string, 'alt', attachment.alt );
			wpBannerWidget.updateInputIfEmpty( widget_id_string, 'description', attachment.description );
			// attempt to populate 'description' with caption if description is blank.
			wpBannerWidget.updateInputIfEmpty( widget_id_string, 'description', attachment.caption );
		},

		// Update input fields if it is empty
		updateInputIfEmpty : function( widget_id_string, name, value ) {
			var field = $("#" + widget_id_string + name);
			if ( field.val() == '' ) {
				field.val(value);
			}
		},

		// Render html for the image.
		imgHTML : function( attachment ) {

			var img_html = '<img src="' + attachment.url + '" ';
			img_html += 'width="' + attachment.width + '" ';
			img_html += 'height="' + attachment.height + '" ';
			if ( attachment.alt != '' ) {
				img_html += 'alt="' + attachment.alt + '" ';
			}
			img_html += '/>';
			return img_html;
		},

		// Hide or display the WordPress image size fields depending on if 'Custom' is selected.
		toggleSizes : function( widget_id, widget_id_string ) {
			if ( $( '#' + widget_id_string + 'size' ).val() == 'tribe_image_widget_custom' ) {
				$( '#' + widget_id_string + 'custom_size_fields').slideDown();
			} else {
				$( '#' + widget_id_string + 'custom_size_fields').slideUp();
			}
		},

		// Update the image height based on the image width.
		changeImgWidth : function( widget_id, widget_id_string ) {
			var aspectRatio = $("#" + widget_id_string + 'aspect_ratio').val();
			if ( aspectRatio ) {
				var width = $( '#' + widget_id_string + 'width' ).val();
				var height = Math.round( width / aspectRatio );
				$( '#' + widget_id_string + 'height' ).val(height);
				//wpBannerWidget.changeImgSize( widget_id, widget_id_string, width, height );
			}
		},

		// Update the image width based on the image height.
		changeImgHeight : function( widget_id, widget_id_string ) {
			var aspectRatio = $("#" + widget_id_string + 'aspect_ratio').val();
			if ( aspectRatio ) {
				var height = $( '#' + widget_id_string + 'height' ).val();
				var width = Math.round( height * aspectRatio );
				$( '#' + widget_id_string + 'width' ).val(width);
				//wpBannerWidget.changeImgSize( widget_id, widget_id_string, width, height );
			}
		}

	};


	/* ____________________________________ WIDGET VISIBILITY ___________________________________ */

	var widgets_shell = $( 'div#widgets-right' );

	function setWidgetMargin( $widget ) {

		if ( $widget.hasClass( 'expanded' ) ) {
			// The expanded widget must be at least 400px wide in order to
			// contain the visibility settings. IE wasn't handling the
			// margin-left value properly.

			if ( $widget.attr( 'style' ) ) {
				$widget.data( 'original-style', $widget.attr( 'style' ) );
			}

			var currentWidth = $widget.width();

			if ( currentWidth < 400 ) {
				var extra = 400 - currentWidth;
				if( isRtl ) {
					$widget.css( 'position', 'relative' ).css( 'right', '-' + extra + 'px' ).css( 'width', '400px' );
				} else {
					$widget.css( 'position', 'relative' ).css( 'left', '-' + extra + 'px' ).css( 'width', '400px' );
				}
				
			}
		}
		else if ( $widget.data( 'original-style' ) ) {
			// Restore any original inline styles when visibility is toggled off.
			$widget.attr( 'style', $widget.data( 'original-style' ) ).data( 'original-style', null );
		}
		else {
			$widget.removeAttr( 'style' );
		}
	}

	$( 'a.display-options' ).each( function() {
		var $displayOptionsButton = $( this ),
			$widget = $displayOptionsButton.closest( 'div.widget' );
		$displayOptionsButton.insertBefore( $widget.find( 'input.widget-control-save' ) );

		// Widgets with no configurable options don't show the Save button's container.
		$displayOptionsButton
			.parent()
				.removeClass( 'widget-control-noform' )
				.find( '.spinner' )
					.remove()
					.css( 'float', 'left' )
					.prependTo( $displayOptionsButton.parent() );

	} );

	widgets_shell.on( 'click.widgetconditions', 'a.add-condition', function( e ) {
		e.preventDefault();
		var $condition = $( this ).closest( 'div.condition' ),
			$conditionClone = $condition.clone().insertAfter( $condition );
		$conditionClone.find( 'select.conditions-rule-major' ).val( '' );
		$conditionClone.find( 'select.conditions-rule-minor' ).html( '' ).attr( 'disabled' );
	} );

	widgets_shell.on( 'click.widgetconditions', 'a.display-options', function ( e ) {
		e.preventDefault();

		var $displayOptionsButton = $( this ),
			$widget = $displayOptionsButton.closest( 'div.widget' );

		$widget.find( 'div.widget-conditional' ).toggleClass( 'widget-conditional-hide' );
		$( this ).toggleClass( 'active' );
		$widget.toggleClass( 'expanded' );
		setWidgetMargin( $widget );

		if ( $( this ).hasClass( 'active' ) ) {
			$widget.find( 'input[name=widget-conditions-visible]' ).val( '1' );
		} else {
			$widget.find( 'input[name=widget-conditions-visible]' ).val( '0' );
		}

	} );

	widgets_shell.on( 'click.widgetconditions', 'a.delete-condition', function( e ) {
		e.preventDefault();

		var $condition = $( this ).closest( 'div.condition' );

		if ( $condition.is( ':first-child' ) && $condition.is( ':last-child' ) ) {
			$( this ).closest( 'div.widget' ).find( 'a.display-options' ).click();
			$condition.find( 'select.conditions-rule-major' ).val( '' ).change();
		} else {
			$condition.detach();
		}
	} );

	widgets_shell.on( 'click.widgetconditions', 'div.widget-top', function() {
		var $widget = $( this ).closest( 'div.widget' ),
			$displayOptionsButton = $widget.find( 'a.display-options' );

		if ( $displayOptionsButton.hasClass( 'active' ) ) {
			$displayOptionsButton.attr( 'opened', 'true' );
		}

		if ( $displayOptionsButton.attr( 'opened' ) ) {
			$displayOptionsButton.removeAttr( 'opened' );
			$widget.toggleClass( 'expanded' );
			setWidgetMargin( $widget );
		}
	} );

	$( document ).on( 'change.widgetconditions', 'select.conditions-rule-major', function() {
		var $conditionsRuleMajor = $ ( this );
		var $conditionsRuleMinor = $conditionsRuleMajor.siblings( 'select.conditions-rule-minor:first' );

		if ( $conditionsRuleMajor.val() ) {
			$conditionsRuleMinor.html( '' ).append( $( '<option/>' ).text( $conditionsRuleMinor.data( 'loading-text' ) ) );

			var data = {
				action: 'widget_conditions_options',
				major: $conditionsRuleMajor.val()
			};

			jQuery.post( ajaxurl, data, function( html ) {
				$conditionsRuleMinor.html( html ).removeAttr( 'disabled' );
			} );
		} else {
			$conditionsRuleMajor.siblings( 'select.conditions-rule-minor' ).attr( 'disabled', 'disabled' ).html( '' );
		}
	} );

})(jQuery);
