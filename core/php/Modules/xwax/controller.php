<?php
/* Copyright (C) 2016 othmar52 <othmar52@users.noreply.github.com>
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
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
$app->get('/xwax/:cmd/:params+', function($cmd, $params) use ($app, $vars){
	$xwax = new \Slimpd\Xwax();
	$xwax->cmd($cmd, $params, $app);
	$app->stop();
});

$app->get('/xwaxstatus(/)', function() use ($app, $vars){
	$xwax = new \Slimpd\Xwax();
	$deckStats = $xwax->fetchAllDeckStats();
	deliverJson($deckStats);
	$app->stop();
});