<?php
/*
[license]
[/license]
 */
require "common.inc";
require "common-guest.inc";
require "html.inc";

php_req_extension("json_encode", "json");

$gp_json = array();
$gp_tag  = '';

function colorquad( &$mbp, $pos )
{
	$color = array();
	for ( $i=0; $i < $mbp['k']; $i += 4 )
	{
		$s = substr($mbp['d'], $pos+$i, 4);
		if ( trim($s, BYTE) == '' )
			$color[] = '1';
		else
		{
			$r = ord( $s[0] );
			$g = ord( $s[1] );
			$b = ord( $s[2] );
			$a = ord( $s[3] );
			$color[] = sprintf("#%02x%02x%02x%02x", $r, $g, $b, $a);
		}
	} // for ( $i=0; $i < $mbp['k']; $i += 4 )

	$cqd = array($color[2] , $color[3] , $color[4] , $color[5]);
	if ( implode('',$cqd) == '1111' )
		$cqd = '';
	return $cqd;
}

function sectquad( &$mbp, $pos )
{
	$float = array();
	for ( $i=0; $i < $mbp['k']; $i += 2 )
		$float[] = str2int($mbp['d'], $pos+$i, 2, true) / 0x10;

	//   1 4    1-2
	//   | | =>   |  , 4-10-8-6
	//   2-3    4-3
	$cdef = array(
		$float[ 4] , $float[ 5] ,
		$float[10] , $float[11] ,
		$float[ 8] , $float[ 9] ,
		$float[ 6] , $float[ 7] ,
	);
	return $cdef;
}
//////////////////////////////
function sectpart( &$mbp, $pfx, $k6, $id6, $no6 )
{
	global $gp_json;

	$data = array();
	for ( $i4=0; $i4 < $no6; $i4++ )
	{
		$p4 = ($id6 + $i4) * $mbp[4]['k'];

		// 0 1 2 3  4   6  8    a    c    e     10   12   14   16
		// sub      s1  -  s0-0 s0-6 s0-c s0-2  s2-0 s2-6 s2-c s2-2
		$sub = substr ($mbp[4]['d'], $p4+ 0, 4);

		$s1 = str2int($mbp[4]['d'], $p4+ 4, 2); // sx,sy
		$s0 = str2int($mbp[4]['d'], $p4+ 8, 2);
		$s2 = str2int($mbp[4]['d'], $p4+16, 2); // dx,dy

		$sqd = sectquad ($mbp[1], $s1*$mbp[1]['k']);
		$cqd = colorquad($mbp[0], $s0*$mbp[0]['k']);
		$dqd = sectquad ($mbp[2], $s2*$mbp[2]['k']);

		$s1 = str2int($sub, 0, 2); // ??
		$s3 = ord( $sub[2] ); // mask
		$s4 = ord( $sub[3] ); // tid

		if ( $gp_json['TexReq'] <= $s4 )
			$gp_json['TexReq'] = $s4 + 1;

		$data[$i4] = array();
		if ( $s1 & 2 )
			continue;

		$data[$i4]['DstQuad'] = $dqd;
		if ( ! empty($cqd) )
			$data[$i4]['ClrQuad']  = $cqd;

		//  1 layer normal
		//  2 layer top
		//  4 gradientFill
		//  8 attack box
		// 10
		// 20
		if ( ($s1 & 4) == 0 )
		{
			$data[$i4]['TexID']   = $s4;
			$data[$i4]['SrcQuad'] = $sqd;
		}

		if ( $s3 != 0 )
		{
			$data[$i4]['Blend'] = array('ADD', 'ONE', 'ONE');
		}

	} // for ( $i4=0; $i4 < $no6; $i4++ )

	$gp_json['Frame'][$k6] = $data;
	return;
}

function sectspr( &$mbp, $pfx )
{
	// s6-s4-s1,s2 [18-18-20,20]
	$len6 = strlen( $mbp[6]['d'] );
	for ( $i6=0; $i6 < $len6; $i6 += $mbp[6]['k'] )
	{
		// 0 4 8 c  10 11  12 13  14  15 16 17
		// - - - -  id     -  -   no  -  -  -
		$id6 = str2int($mbp[6]['d'], $i6+0x10, 2);
		$no6 = str2int($mbp[6]['d'], $i6+0x14, 1);
		// DO NOT skip numbering
		// JSON will become {object} instead [array]

		$k6 = $i6 / $mbp[6]['k'];
		sectpart($mbp, $pfx, $k6, $id6, $no6);

	} // for ( $i6=0; $i6 < $len6; $i6 += $mbp[6]['k'] )
	return;
}
//////////////////////////////
function sectanim( &$mbp, $pfx )
{
	global $gp_json;

	// s9-sa-s8 [30-8-20]
	$len9 = strlen( $mbp[9]['d'] );
	for ( $i9=0; $i9 < $len9; $i9 += $mbp[9]['k'] )
	{
		// 0 4 8 c  10
		// - - - -  name
		// 28 29  2a  2b 2c 2d 2e 2f
		// id     no  -  -  -  -  -
		$name = substr0($mbp[9]['d'], $i9+0x10);
		$id9  = str2int($mbp[9]['d'], $i9+0x28, 2);
		$no9  = str2int($mbp[9]['d'], $i9+0x2a, 1);

		for ( $ia=0; $ia < $no9; $ia++ )
		{
			$pa = ($id9 + $ia) * $mbp[10]['k'];

			// 0 1  2 3  4 5 6 7
			// id   no   - - - -
			$ida = str2int($mbp[10]['d'], $pa+0, 2);
			$noa = str2int($mbp[10]['d'], $pa+2, 2);

			$ent = array();
			for ( $i8=0; $i8 < $noa; $i8++ )
			{
				$p8 = ($ida + $i8) * $mbp[8]['k'];

				// 0   2 4  6   8 c 10 14 18 1c
				// id  - -  no  - - -  -  -  -
				$id8 = str2int($mbp[8]['d'], $p8+0, 2);
				$no8 = str2int($mbp[8]['d'], $p8+6, 2);

				$ent[] = array($id8,$no8);

			} // for ( $i8=0; $i8 < $noa; $i8++ )

			$gp_json['Animation'][$name][$ia] = $ent;
		} // for ( $ia=0; $ia < $no9; $ia++ )

	} // for ( $i9=0; $i9 < $len9; $i9 += $mbp[9]['k'] )

	return;
}
//////////////////////////////
function loadmbp( &$mbp, $sect, $pfx )
{
	$offs = array();
	$offs[] = strrpos($mbp, "FEOC");
	foreach ( $sect as $k => $v )
	{
		$b1 = str2int($mbp, $v['p'], 4);
		if ( $b1 == 0 )
			continue;
		$offs[] = $b1;
		$sect[$k]['o'] = $b1;
	}
	sort($offs);

	foreach ( $sect as $k => $v )
	{
		if ( ! isset( $v['o'] ) )
			continue;
		$id = array_search($v['o'], $offs);
		$sz = int_floor($offs[$id+1] - $v['o'], $v['k']);
		$dat = substr($mbp, $v['o'], $sz);

		$sect[$k]['d'] = $dat;
	} // foreach ( $sect as $k => $v )

	$mbp = $sect;
	return;
}
//////////////////////////////
function odin( $fname )
{
	$mbp = load_file($fname);
	if ( empty($mbp) )  return;

	if ( substr($mbp,0,4) != "FMBP" )
		return;

	if ( str2int($mbp, 8, 4) != 0xa0 )
		return printf("DIFF not 0xa0  %s\n", $fname);

	// $siz = str2int($mbp, 4, 3);
	// $hdz = str2int($mbp, 8, 3);
	// $len = 0x10 + $hdz + $siz;
	$pfx = substr($fname, 0, strrpos($fname, '.'));

	//   0 1 2 |     1-0 2-1 3-2
	// 3 4 5 6 | 6-3 5-4 9-5 7-6
	// 7 8 9 a | 8-7 4-8 a-9 s-a
	// staff_dummy.mbp
	//        a0 1a0 1e0 |      8*20 2*20 8*20
	//     - 550   - 2e0 |    - 2*18    - 2*18
	//   310 370 580 850 | 2*30 f*20 f*30 d*8
	// s9[+28] = c+1 => sa
	// sa[+ 0] = e+1 => s8
	// s8[]
	//
	// gwendlyn.mbp
	//            a0   6fa0   8260 |         378*20  96*20 9078*20
	// 129160 157080 19a490 12dee0 |  f8*50 2cd6*18 209*8   376*18
	// 1331f0 13b440 19b4d8 19da58 | 2b7*30  de2*20  c8*30  193*8
	// s9[+28] =  190+3  => sa
	// sa[+ 0] =  ddf+3  => s8
	// s8[+ 0] =  375    => s6 , [+ 4] = 2b6
	// s6[+10] = 2cb9+1d => s4 , [+12] = 208+1 => s5
	// s4[]
	// s5[+ 0] =   f7    => s3
	//
	// s9-sa-s8-s6-s4-?
	$sect = array(
		array('p' => 0x54 , 'k' => 0x20), // 0
		array('p' => 0x58 , 'k' => 0x20), // 1
		array('p' => 0x5c , 'k' => 0x20), // 2
		array('p' => 0x60 , 'k' => 0x50), // 3 area=0
		array('p' => 0x64 , 'k' => 0x18), // 4
		array('p' => 0x68 , 'k' => 0x08), // 5 area=0
		array('p' => 0x6c , 'k' => 0x18), // 6
		array('p' => 0x70 , 'k' => 0x30), // 7
		array('p' => 0x74 , 'k' => 0x20), // 8
		array('p' => 0x78 , 'k' => 0x30), // 9
		array('p' => 0x7c , 'k' => 0x08), // 10
	);
	loadmbp($mbp, $sect, $pfx);

	global $gp_json, $gp_tag;
	if ( $gp_tag == '' )
		return;
	$gp_json = load_idtagfile($gp_tag);
	$gp_json['TexReq'] = 0;

	sectanim($mbp, $pfx);
	sectspr ($mbp, $pfx);

	// JSON_PRETTY_PRINT
	// JSON_FORCE_OBJECT
	if ( ! empty($gp_json) )
		file_put_contents("$fname.quad", json_encode($gp_json));
	return;
}

for ( $i=1; $i < $argc; $i++ )
{
	if ( $argv[$i] == '-grim' )
		$gp_tag = 'ps2_grim';
	else
	if ( $argv[$i] == '-odin' )
		$gp_tag = 'ps2_odin';
	else
		odin( $argv[$i] );
}
