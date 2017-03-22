module GoodsPack {
	var $ = jQuery; 
	export class Search {

		static $searchInput :string = "#goodspack_search_input";
		static $_searchInput :string = "goodspack_search_input";
		static $categoryButton = "#goodspack_category_button";
		static $btnSearch = ".btn-search";
		static $categoryFilderBox = ".goodspack_categories_filter_box";
		static $_categoryFilderBox = "goodspack_categories_filter_box";

		static $categoryRadio = ".goodspack_cat-option";
		static $categoryItemHomePage = ".goodspack_category_item";

		static $category_html = "";
		constructor() {
			
			Search.$category_html = $.gp( Search.$categoryFilderBox ).remove();

			$.gp( Search.$categoryButton ).mousedown( Search.CategoryButtonClicked );
			$.gp( Search.$btnSearch ).click( Search.InitializeSearch );
			$.gp( Search.$searchInput ).keyup( AutoFill.SearchKeyUp );
			$.gp( Search.$searchInput ).focus( AutoFill.SearchFocus );
			$.gp( Search.$searchInput ).focusout( AutoFill.SearchFocusOut );
			// Homepage category
			$( Search.$categoryItemHomePage ).click( Search.SetCat );
			$("body").append( Search.$category_html );
		}

		public static CategoryButtonClicked(e : JQueryEventObject) {
			
			e.preventDefault();
			if( $( Search.$categoryFilderBox ).is(":visible") )
				$( Search.$categoryFilderBox ).hide();
			else
			{
				e.stopPropagation();
				GoodsPack.AutoFill.HideAutoFill( undefined );
				Search.CategoryBlockShow();
				$( GoodsPack.Search.$categoryRadio ).off("change").change( GoodsPack.Search.ChangeCategoryRadio )
			}
		}

		private static CategoryBlockShow() {
			
			$( Search.$categoryFilderBox ).show();
			
			Search.CategoryPosition();
		}

		public static CategoryBlockHideEvent ( e : JQueryEventObject ) {

			if( $(e.target).hasClass( Search.$_categoryFilderBox ) || $( Search.$categoryFilderBox ).find(e.target).length != 0 )
				return;

			$( Search.$categoryFilderBox ).hide();
		}

		public static CategoryPosition(){

			let css: any = {};
			css.top = $.gp( Search.$categoryButton ).outerHeight() + $.gp( Search.$categoryButton ).offset().top + 1; // 1 it's for a little border))
			css.left = $.gp( Search.$categoryButton ).offset().left;

			// absolute position
			$( Search.$categoryFilderBox ).css( css );
		}

		public static InitializeSearch() {
			
			var keyword = $.gp( Search.$searchInput ).val( );
			
			// hide show blocks
			if( $.gp( "." + Index.$_full ).length == 0 )
				GoodsPack.Index.SecondPage();
			
			// init route
			GoodsPack.Route
				.Set("keyword", keyword )
				.Build( undefined );

			// update in paging
			$.gp( Paging.$paging_info ).text( keyword );
			
			// focusout from input for hide auto fill list
			$.gp( AutoFill.$autoFill ).addClass("gp-hidden");
			$.gp( Search.$searchInput ).focusout();
			
			Index.ShowHideBlocks();
			// Load Products
			GoodsPack.App.Products.Load( true );
		}

		public static SetSearch( $val : string ) {
			$.gp( Search.$searchInput ).val( $val );
			return this;
		}

		
		public static ChangeCategoryRadio ( e : JQueryEventObject ) {
			
			var arr :any = [];
			$( Search.$categoryRadio + ":checked").each(function(){
				arr.push( $(this).attr("data-cat-id") )
			});
			var cat_ids = arr.join(",");
			if( cat_ids == "" )
				cat_ids = "-";
			
			GoodsPack.Route
				.Set("categories", cat_ids )
				.Build( undefined );

			// Homepage category
			if( e.type == "click" )
				GoodsPack.Search.InitializeSearch();
		}

		public static SetCat() {

			var cat_id = $(this).attr("data-cat-id");
			
			GoodsPack.Route
				.Set("categories", cat_id )
				.Build( undefined );

			Search.SetCategory( cat_id );

			GoodsPack.Search.InitializeSearch();
		}
		public static SetCategory( $cat_ids : string| number ) : void {
			
			var $ids = $cat_ids.toString().split(",");
			for( let i = 0; i < $ids.length; i++) {

				let $selector = "[data-cat-id=" + $ids[i] + "]";
				
				if( $( $selector ).length == 0 )  // if not found 
					continue;					
				$( $selector ).prop( "checked", true );
			}
		}
	}
}
