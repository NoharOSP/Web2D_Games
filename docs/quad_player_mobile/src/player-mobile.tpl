<!doctype html>
[license]
Copyright (C) 2019 by Rufas Wan

This file is part of Web2D Games.
    <https://github.com/rufaswan/Web2D_Games>

Web2D Games is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Web2D Games is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Web2D Games.  If not, see <http://www.gnu.org/licenses/>.
[/license]
<html><head>

<meta charset='utf-8'>
<meta name='viewport' content='width=device-width, initial-scale=1'>
<title>Quad Player - Mobile</title>
@@<player.css>@@
@@<player.js>@@
@@<quad.js>@@
</head><body>

<div id='debugger'>
	<input type='file' id='input_file' multiple class='hidden'>
	<div id='debugger_top_nav'>
		<p id='debugger_top_row_1'>
			<button id='btn_view'>view</button>
			<button id='btn_upload' data-id='0'>upload</button>
		</p>
	</div>

	<button>dummy</button>
	<h1 id='quad_version'></h1>
	<h2>Files</h2>
	<ol id='debugger_files'></ol>
	<div id='quad_data'></div>
	<h3>Logs</h3>
	<textarea id='logger' disabled></textarea>
	<p>
		<a href='https://rufaswan.github.io/Web2D_Games/quad_player_mobile/player-mobile.tpl.html' target='_blank'>Latest version</a> -
		<a href='https://rufaswan.github.io/Web2D_Games/quad_player_mobile/spec.html' target='_blank'>QUAD File Spec</a> -
		<a href='https://github.com/rufaswan/Web2D_Games' target='_blank'>Github</a>
	</p>

	<div id='export_div' data-type='' data-id=''>
		<p id='export_name'></p>
		<p>
			START = <span id='export_start'>0</span>
			<input id='export_range' type='range' min='0' max='0' step='1' value='0'>
		</p>
		<p>
			ZOOM = <span id='export_zoom'>1.0</span>
			<input id='export_times' type='range' min='0.1' max='10.0' step='0.1' value='1.0'>
		</p>
		<p>
			<button onclick='button_export_type(this);'>png</button>
			<button onclick='button_export_type(this);'>zip</button>
			<button onclick='button_export_type(this);'>rgba</button>
		</p>
	</div>
</div>

<div id='viewer'>
	@@<bg-q3.png>@@
	<canvas id='canvas'>Canvas not supported</canvas>
	<div id='viewer_top_nav'>
		<p id='viewer_top_row_1'>
			<button id='btn_debug'>debug</button>
			<button id='btn_hits' class='btn_on'>hit</button>
			<button id='btn_flipx' class='btn_off'>X</button>
			<button id='btn_lines'>line</button>
		</p>
	</div>
	<div id='viewer_bottom_nav'>
		<p id='viewer_bottom_row_1'>
			<button id='btn_prev'>&lt;&lt;</button>

			<button id='btn_autonext'>auto</button>
			<button id='btn_cur'>0</button>

			<button id='btn_next'>&gt;&gt;</button>
		</p>
	</div>
</div>

<script>
var HTML = get_html_id();
var QuadList = [];
var SELECTED = '';

(function(){
	if ( ! QUAD.gl.init(HTML.canvas) )
		return;

	HTML.quad_version.innerHTML = 'Quad Player ' + QUAD.version;

	// BETWEEN DEBUGGER-VIEWER
	HTML.btn_view.addEventListener('click', function(){
		display_viewer(HTML, true);
	});
	HTML.btn_debug.addEventListener('click', function(){
		display_viewer(HTML, false);
		HTML.logger.innerHTML = QUAD.func.console();
	});

	var UPLOAD_ID = -1;
	HTML.btn_upload.addEventListener('click', function(){
		UPLOAD_ID = this.getAttribute('data-id');
		HTML.input_file.click();
		HTML.logger.innerHTML = QUAD.func.console();
	});
	HTML.input_file.addEventListener('change', function(){
		QUAD.func.log('QuadList[]', UPLOAD_ID);
		if ( QUAD.func.is_undef( QuadList[ UPLOAD_ID ] ) )
			QuadList[ UPLOAD_ID ] = new QuadData(QuadList);
		var qdata = QuadList[ UPLOAD_ID ];

		var promises = [];
		for ( var up of this.files )
			promises.push( QUAD.func.upload_promise(up, qdata) );

		Promise.all(promises).then(function(resolve){
			qdata_filetable(qdata, HTML.debugger_files);
			if ( qdata.name ){
				HTML.quad_data.innerHTML = '';
				document.title = qdata.name + ' [Quad Player ' + QUAD.version + ']';

				var buffer = qdata_tagtable(qdata.quad.tag);
				HTML.quad_data.innerHTML += buffer;

				var quad_main = quad_mainlist(qdata.quad);
				if ( quad_main === -1 )
					return;

				var buffer = '<h2>' + quad_main + '</h2>';
				buffer += '<ul>';
				qdata.quad[quad_main].forEach(function(v,k){
					if ( ! v )
						return;
					buffer += qdata_listing(qdata, quad_main, k);

				});
				buffer += '</ul>';
				HTML.quad_data.innerHTML += buffer;
			} // if ( qdata.name )
			HTML.logger.innerHTML = QUAD.func.console();
		});
	});
	HTML.export_range.addEventListener('change', function(){
		HTML.export_start.innerHTML = this.value;
	});
	HTML.export_times.addEventListener('change', function(){
		HTML.export_zoom.innerHTML = this.value;
	});

	// VIEWER
	var IS_VIEWER_NAV = true;
	var IS_AUTOZOOM   = true;
	HTML.canvas.addEventListener('click', function(){
		if ( IS_VIEWER_NAV ) {
			HTML.viewer_top_nav.style.display    = 'none';
			HTML.viewer_bottom_nav.style.display = 'none';
		} else {
			HTML.viewer_top_nav.style.display    = 'block';
			HTML.viewer_bottom_nav.style.display = 'block';
		}
		IS_VIEWER_NAV = ! IS_VIEWER_NAV;
	});
	HTML.btn_lines.addEventListener('click', function(){
		if ( ! QuadList[0] )
			return;
		QuadList[0].is_lines = ! QuadList[0].is_lines;
		HTML.btn_lines.innerHTML = ( QuadList[0].is_lines ) ? 'line' : 'tex';
	});
	HTML.btn_hits.addEventListener('click', function(){
		if ( ! QuadList[0] )
			return;
		if ( HTML.btn_hits.classList.contains('btn_on') ){
			btn_toggle(HTML.btn_hits, -1);
			QuadList[0].is_hits = false;
		} else {
			btn_toggle(HTML.btn_hits, 1);
			QuadList[0].is_hits = true;
		}
	});
	HTML.btn_flipx.addEventListener('click', function(){
		if ( ! QuadList[0] )
			return;
		if ( HTML.btn_flipx.classList.contains('btn_on') ){
			btn_toggle(HTML.btn_flipx, -1);
			QuadList[0].is_flipx = false;
		} else {
			btn_toggle(HTML.btn_flipx, 1);
			QuadList[0].is_flipx = true;
		}
	});

	var IS_BTN_CLICK = 0;
	var IS_AUTONEXT  = false;
	HTML.btn_prev.addEventListener('click', function(){
		if ( IS_AUTONEXT ){
			btn_toggle(HTML.btn_next, -1);
			if ( HTML.btn_prev.classList.contains('btn_on') ){
				btn_toggle(HTML.btn_prev, -1);
				IS_BTN_CLICK = 0;
			} else {
				btn_toggle(HTML.btn_prev, 1);
				IS_BTN_CLICK = -1;
			}
		} else
			IS_BTN_CLICK = -1;
	});
	HTML.btn_next.addEventListener('click', function(){
		if ( IS_AUTONEXT ){
			btn_toggle(HTML.btn_prev, -1);
			if ( HTML.btn_next.classList.contains('btn_on') ){
				btn_toggle(HTML.btn_next, -1);
				IS_BTN_CLICK = 0;
			} else {
				btn_toggle(HTML.btn_next, 1);
				IS_BTN_CLICK = 1;
			}
		} else
			IS_BTN_CLICK = 1;
	});
	HTML.btn_autonext.addEventListener('click', function(){
		if ( IS_AUTONEXT ){
			btn_toggle(HTML.btn_prev, 0);
			btn_toggle(HTML.btn_next, 0);
			IS_AUTONEXT = false;
		} else {
			btn_toggle(HTML.btn_prev, -1);
			btn_toggle(HTML.btn_next, -1);
			IS_AUTONEXT = true;
		}
	});

	var FPS_DRAW = 0;
	var CAMERA = QUAD.math.matrix4();
	var COLOR  = [1,1,1,1];
	function render(){
		requestAnimationFrame(render);
		if ( HTML.viewer.style.display !== 'block' )
			return;
		if ( ! QuadList[0] || ! QuadList[0].name )
			return;
		var qdata = QuadList[0];

		// auto forward by 60/8 fps = 7.5 fps
		if ( (FPS_DRAW & 7) === 0 ){
			btn_prev_next(qdata, IS_BTN_CLICK);
			if ( ! IS_AUTONEXT )
				IS_BTN_CLICK = 0;
		}

		// update/redraw only when changed
		if ( QUAD.gl.is_canvas_resized() || QUAD.func.is_changed(qdata) ){
			CAMERA = QUAD.func.viewer_camera(qdata, IS_AUTOZOOM);
			HTML.btn_cur.innerHTML = qdata.attach.id + '/' + qdata.anim_fps;
			HTML.logger.innerHTML  = QUAD.func.console();

			QUAD.func.qdata_clear(qdata);
			QUAD.func.qdata_draw(qdata, CAMERA, COLOR);
			if ( ! qdata.is_draw ){
				HTML.btn_cur.innerHTML = 'END';
			}
		}

		FPS_DRAW = (FPS_DRAW + 1) & 0xff;
	}
	render();
})();
</script>

</body></html>
