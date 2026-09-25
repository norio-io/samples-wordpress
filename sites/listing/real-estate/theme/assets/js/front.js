/**
 * ひなた不動産（架空）— 公開側
 *
 * 物件一覧の絞り込みは、幅 1024px 以上では常に開いた左段とし、それ未満では結果の上に折りたたんで置く。
 * スクリプトが動かない環境では、開いたまま表示する（inc/blocks.php で open を付けて出力する）。
 */
( function () {
	const wide = window.matchMedia( '(min-width: 1024px)' );
	const details = document.querySelectorAll( '.hinata-search__details' );
	if ( ! details.length ) {
		return;
	}
	const apply = () => {
		details.forEach( ( el ) => {
			el.open = wide.matches;
		} );
	};
	apply();
	wide.addEventListener( 'change', apply );
} )();
