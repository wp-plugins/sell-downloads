jQuery( function( $ )
	{
		// Correct the header and items width
		var correct_header = function()
			{
				$( '.sell-downloads-items,.sell-downloads-pagination' ).each(
					function()
					{

						var e = $( this );
						if( e.parents( '.widget' ).length == 0 )
						{
							e.css( 'width', $( '.sell-downloads-header' ).outerWidth( ) );
						}
					}
				);
			};
		
		correct_header();
		
		// Replace the popularity texts with the stars 
		var popularity_top = 0;
		$( '.product-popularity' ).each(
			function()
			{
				var e = $( this ),
					p = parseInt( e.find( 'span' ).remove().end().text().replace( /\s/g, '' ) );
					
				e.text( '' ).attr( 'popularity', p );
				popularity_top = Math.max( popularity_top, p );
			}
		);
		
		$( '.product-popularity' ).each(
			function()
			{
			
				var e = $( this ),
					p = e.attr( 'popularity' ),
					str = '',
					active = 0;

				if( popularity_top > 0 )
				{
					active = Math.ceil( p / popularity_top * 100 / 20 );
				}
				
				for( var i = 0; i < active; i++ )
				{
					str += '<div class="star-active"></div>';
				}
				
				for( var i = 0, h = 5 - active; i < h; i++ )
				{
					str += '<div class="star-inactive"></div>';
				}
				e.html( str );
			}
		);
		
		// Correct the images heights
		var min_height = Number.MAX_VALUE
			correct_heights = function()
			{
			$( '.sell-downloads-items .product-cover img' ).each(
				function()
				{
					var e = $( this );
					min_height = Math.min( e.height(), min_height );
				}
			);

			if( min_height != Number.MAX_VALUE )
			{
				$( '.sell-downloads-items .product-cover' ).css( { 'height': min_height+'px', 'overflow': 'hidden' } );
			}	
			
			$( '.product-cover' ).append( $( '<div class="sd-inner-shadow"></div>' ) );
			
			// Correct the item heights
			var	height_arr = [],
				max_height = 0;
			$( '.sell-downloads-items' ).children( 'div' ).each(
				function()
				{
					var e = $( this );
					if( e.hasClass( 'sell-downloads-item' ) )
					{
						max_height = Math.max( e.height(), max_height );
					}
					else
					{
						height_arr.push( max_height );
						max_height = 0;
					}
				}
			);
			
			if( height_arr.length )
			{
				$( '.sell-downloads-items' ).children( 'div' ).each(
					function()
					{
						var e = $( this );
						if( e.hasClass( 'sell-downloads-item' ) )
						{
							e.height( height_arr[ 0 ] );
						}
						else
						{
							height_arr.splice( 0, 1 );
						}
					}
				);
			}	
		};
		
		correct_heights();
		
		// Modify the price box
		$( '.product-price' ).each(
			function()
			{
				var e = $( this );
				e.closest( 'div' ).addClass( 'price-box' ).find( 'span:not(.product-price),span.invalid' ).remove();
			}
		);
		
		// Modify the single pages structure
		$( '.sell-downloads-song .left-column' ).append( $('<div></div>').html( $( '.sell-downloads-song .right-column' ).html() ) );
		$( '.sell-downloads-song .right-column' ).html( '' ).append( $( '.sell-downloads-song .bottom-content' ) );
		$( '.sell-downloads-collection .left-column' ).append( $('<div></div>').html( $( '.sell-downloads-collection .right-column' ).html() ) );
		$( '.sell-downloads-collection .right-column' ).html( '' ).append( $( '.sell-downloads-collection .bottom-content' ) );
		
		// Modify the shopping cart design
		$( '.sd-shopping-cart-list,.sd-shopping-cart-resume' ).wrap( '<div class="sd-shopping-cart-wrapper"></div>' );
	} 	
);