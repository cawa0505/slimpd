<?php
/* Copyright (C) 2015-2016 othmar52 <othmar52@users.noreply.github.com>
 *
 * This file is part of sliMpd - a php based mpd web client
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Affero General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.	See the GNU Affero General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.	If not, see <http://www.gnu.org/licenses/>.
 */

// Routes

// library routes
$app->get('[/]', 'Slimpd\Modules\Library\Controller:indexAction');
$app->get('/library[/]', 'Slimpd\Modules\Library\Controller:indexAction');

// filebrowser routes
$app->get('/filebrowser', 'Slimpd\Modules\filebrowser\Controller:index')->setName('filebrowser');
$app->get('/filebrowser/[{itemParams:.*}]', 'Slimpd\Modules\filebrowser\Controller:dircontent');
$app->get('/markup/widget-directory/[{itemParams:.*}]', 'Slimpd\Modules\filebrowser\Controller:widgetDirectory');
$app->get('/deliver/[{itemParams:.*}]', 'Slimpd\Modules\filebrowser\Controller:deliverAction');

// image routes
$app->get('/imagefallback-{imagesize}/{type}', 'Slimpd\Modules\images\Controller:fallback')->setName('imagefallback');
$app->get('/image-{imagesize}/album/{itemUid}', 'Slimpd\Modules\images\Controller:album')->setName('imagealbum');
$app->get('/image-{imagesize}/track/{itemUid}', 'Slimpd\Modules\images\Controller:track');
$app->get('/image-{imagesize}/id/{itemUid}', 'Slimpd\Modules\images\Controller:bitmap');
$app->get('/image-{imagesize}/path/[{itemParams:.*}]', 'Slimpd\Modules\images\Controller:path');

// album routes
$app->get('/album/{itemUid}', 'Slimpd\Modules\album\Controller:detailAction');
$app->get('/markup/albumtracks/{itemUid}', 'Slimpd\Modules\album\Controller:albumTracksAction');
$app->get('/markup/widget-album/{itemUid}', 'Slimpd\Modules\album\Controller:widgetAlbumAction');
$app->get('/albums/page/{currentPage}/sort/{sort}/{direction}', 'Slimpd\Modules\album\Controller:listAction');

// track routes
$app->get('/markup/mpdplayer', 'Slimpd\Modules\Track\Controller:mpdplayerAction');
$app->get('/markup/localplayer', 'Slimpd\Modules\Track\Controller:localplayerAction');
$app->get('/markup/xwaxplayer', 'Slimpd\Modules\Track\Controller:xwaxplayerAction');
$app->get('/markup/widget-trackcontrol', 'Slimpd\Modules\Track\Controller:widgetTrackcontrolAction');
$app->get('/markup/widget-xwax', 'Slimpd\Modules\Track\Controller:widgetXwaxAction');
$app->get('/markup/widget-deckselector', 'Slimpd\Modules\Track\Controller:widgetDeckselectorAction');
$app->get('/markup/standalone-trackview', 'Slimpd\Modules\Track\Controller:standaloneTrackviewAction');

// artist routes
$app->get('/artists/[{itemParams:.*}]', 'Slimpd\Modules\Artist\Controller:listAction');

// genre routes
$app->get('/genres/[{itemParams:.*}]', 'Slimpd\Modules\Genre\Controller:listAction');

// label routes
$app->get('/labels/[{itemParams:.*}]', 'Slimpd\Modules\Label\Controller:listAction');

// tools routes
$app->get('/css/spotcolors.css', 'Slimpd\Modules\Tools\Controller:spotcolorsCssAction');
$app->get('/css/localplayer/{relPathHash}', 'Slimpd\Modules\Tools\Controller:localPlayerCssAction');
$app->get('/css/mpdplayer/{relPathHash}', 'Slimpd\Modules\Tools\Controller:mpdPlayerCssAction');
$app->get('/css/xwaxplayer/{relPathHash}', 'Slimpd\Modules\Tools\Controller:xwaxPlayerCssAction');


// waveformgenerator routes
$app->get('/audiosvg/width/{width}/[{itemParams:.*}]', 'Slimpd\Modules\WaveformGenerator\Controller:svgAction');
$app->get('/audiojson/resolution/{width}/[{itemParams:.*}]', 'Slimpd\Modules\WaveformGenerator\Controller:jsonAction');


// mpd
$app->get('/mpdstatus[/]', 'Slimpd\Modules\Mpd\Controller:mpdstatusAction');
$app->get('/mpdctrl/{cmd}', 'Slimpd\Modules\Mpd\Controller:cmdAction');
$app->get('/mpdctrl/{cmd}/[{item:.*}]', 'Slimpd\Modules\Mpd\Controller:cmdAction');
$app->get('/playlist/page/{pagenum}', 'Slimpd\Modules\Mpd\Controller:playlistAction');

