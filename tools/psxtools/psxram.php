<?php
require "common.inc";

// extract RAM section from uncompressed save states
function subram( &$file )
{
	// ePSXe PlayStation emulator (Windows + Linux)
	if ( substr($file, 0, 5) == 'ePSXe' )
	{
		echo "DETECT emulator = ePSXe\n";
		return substr($file, 0x1ba, 0x200000);
	}

	// pSXfin PlayStation emulator (Windows + Linux)
	if ( substr($file, 0, 7) == "ARS2CPU" || substr($file, 0, 6) == "ARSCPU" )
	{
		echo "DETECT emulator = pSXfin\n";
		$st = 0;
		$ed = 0x800;
		while ( $st < $ed )
		{
			if ( substr($file, $st, 3) == "RAM" )
				return substr($file, $st + 0xc, 0x200000);
			$st += 4;
		}
		return "";
	}

	// no$psx PlayStation emulator (Windows)
	if ( substr($file, 0, 15) == 'NO$PSX SNAPSHOT' )
	{
		echo "DETECT emulator = nocash PSX\n";
		$ed = strlen($file);
		$st = 0x40;
		while ( $st < $ed )
		{
			$mgc = substr($file, $st+0, 4);
			switch ( $mgc )
			{
				case "CORE":
				case "BIOS":
					$len = str2int($file, $st+8, 4);
					printf("%8x , %8x , $mgc\n", $st, $len);
					$st += ($len + 12);
					break;
				case "MRAM":
					$len = str2int($file, $st+8, 4);
					printf("%8x , %8x , $mgc\n", $st, $len);
					return substr($file, $st + 12, $len);
				default:
					printf("%8x , $mgc\n", $st);
					return "";
			} // switch ( $mgc )
		} // while ( $st < $ed )
		return "";
	}

	// no$gba Gameboy Advance + Nintendo DS emulator (Windows)
	if ( substr($file, 0, 15) == 'NO$GBA SNAPSHOT' )
	{
		echo "DETECT emulator = nocash GBA\n";
		$ed = strlen($file);
		$st = 0x40;
		while ( $st < $ed )
		{
			$mgc = substr($file, $st+0, 4);
			switch ( $mgc )
			{
				case "VAL2":
				case "IOP7":
				case "DTCM":
				case "ITCM":
				case "RAMS":
				case "RAM7":
				case "WIFI":
				case "VALS":
					$len = str2int($file, $st+8, 4);
					printf("%8x , %8x , $mgc\n", $st, $len);
					$st += ($len + 12);
					break;
				case "RAM2":
					$len = str2int($file, $st+8, 4);
					printf("%8x , %8x , $mgc\n", $st, $len);
					return substr($file, $st + 12, $len);
				default:
					printf("%8x , $mgc\n", $st);
					return "";
			} // switch ( $mgc )
		} // while ( $st < $ed )
		return "";
	}

	// Yabause Saturn emulator (Linux)
	if ( substr($file, 0, 3) == 'YSS' )
	{
		echo "DETECT emulator = Yabause\n";
		$ed = strlen($file);
		$st = 0x14;
		while ( $st < $ed )
		{
			$mgc = substr($file, $st+0, 4);
			switch ( $mgc )
			{
				case "CART":
				case "CS2 ":
				case "MSH2":
				case "SSH2":
				case "SCSP":
				case "SCU ":
				case "VDP1":
				case "SMPC":
				case "VDP2":
					$len = str2int($file, $st+8, 4);
					printf("%8x , %8x , $mgc\n", $st, $len);
					save_file("ram/$mgc", substr($file, $st+12, $len));
					$st += ($len + 12);
					break;
				case "OTHR":
					$len = str2int($file, $st+8, 4);
					printf("%8x , %8x , $mgc\n", $st, $len);
					$st += (12 + 0x10000);
					$ram = "";
					for ( $i=0; $i < 0x100000; $i += 2 )
						$ram .= $file[$st+$i+1] . $file[$st+$i+0];
					return $ram;
				default:
					printf("%8x , $mgc\n", $st);
					return "";
			} // switch ( $mgc )
		} // while ( $st < $ed )
		return "";
	}

	return "";
}

function psxram( $fname )
{
	$file = file_get_contents($fname);
	if ( empty($file) )  return;

	$ram = subram($file);
	if ( empty($ram) )  return;

	$base = preg_replace("|[^a-zA-Z0-9]|", '_', $fname);
	save_file("$base.ram", $ram);
	return;
}

for ( $i=1; $i < $argc; $i++ )
	psxram( $argv[$i] );
