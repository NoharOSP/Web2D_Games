#!/bin/bash
<<'////'
[license]
Copyright (C) 2019 by Rufas Wan

This file is part of Web2D_Games. <https://github.com/rufaswan/Web2D_Games>

Web2D_Games is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Web2D_Games is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Web2D_Games.  If not, see <http://www.gnu.org/licenses/>.
[/license]
////

rgba="/tmp/rgba2bmp.php"
[ -f "$rgba" ] || exit
[ $(which mogrify) ] || exit
export rgba

function rgba2png
{
	[ -f "$1" ] || continue
	php.sh $rgba "$1"
	mogrify -format png \
		-density 72x72 \
		-interlace none \
		-strip \
		"$1".bmp
	rm "$1"
	rm "$1".bmp
}
export -f rgba2png

find . -iname "*.rgba"   | xargs  -I {} bash -c 'rgba2png "$@"' _ {}
find . -iname "*.rgba.*" | xargs  rename .rgba.  .