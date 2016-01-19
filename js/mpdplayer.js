var pollInterval = null; // do we need this in window scope?

$(document).ready(function(){
	nowPlayingSongId = 0;
	
	pollMpdData();
	
	$('body').on('click', '.mpd-ctrl-seekbar', function(e){
		// TODO: how to respect parents padding (15px) on absolute positioned div with width 100% ?
		var percent = Math.round((e.pageX - $(this).offset().left) / (($(this).width()+15)/100));
		$.ajax({
			url: '/mpdctrl/seekPercent/' + percent
		}).done(function(response){
			refreshInterval();
		});
		
    	$('.mpd-status-progressbar').css('width', 'calc('+ percent+'% - 15px)');
	});
});


function refreshInterval() {
	clearInterval(pollInterval);
	pollMpdData();
}

// IMPORTANT TODO: how to avoid growing memory consumption on those frequent poll-requests?
function pollMpdData(){
    $.get('/mpdstatus', function(data) {
    	data = JSON.parse(data);
    	
    	
    	
    	['repeat', 'random', 'consume'].forEach(function(prop) {
		    if(data[prop] == '1') {
    		$('.mpd-status-'+prop).addClass('active');
	    	} else {
	    		$('.mpd-status-'+prop).removeClass('active');
	    	}
		});
		
		
		// TODO: find out why this snippet does not work
		//if(data.state !== 'play' && $('.mpd-status-playpause').hasClass('fa-pause')) {
		//	$('.mpd-status-playpause').toggleClass('fa-pause fa-play');
		//}
		
		if(data.state == 'play') {
			$('.mpd-status-playpause').addClass('fa-pause');
			$('.mpd-status-playpause').removeClass('fa-play');
		} else {
			$('.mpd-status-playpause').removeClass('fa-pause');
			$('.mpd-status-playpause').addClass('fa-play');
		}
		
    	$('.mpd-status-elapsed').text(formatTime(data.elapsed));
    	$('.mpd-status-total').text(formatTime(data.duration));
    	
    	// TODO: simulate seamless progressbar-growth and seamless secondscounter
    	// TODO: how to respect parents padding on absolute positioned div with width 100% ?
    	$('.mpd-status-progressbar').css('width', 'calc('+ data.percent+'% - 15px)');
    	
    	
		// TODO: is this the right place for trigger local-player-favicon-update? - for now it is convenient to use this existing interval...
    	drawFavicon(data.percent, data.state);


    	
    	// update trackinfo only onTrackChange()
    	if(nowPlayingSongId != data.songid) {
    		nowPlayingSongId = data.songid;
    		$('.toogle-tooltip').tooltip('hide');
    		$.ajax({
    			url: '/markup/mpdplayer'
    		}).done(function(response){
    			//console.log(response);
    			$('.player-mpd').html(response);
    			drawTimeGrid(data.duration, 'player-mpd');
    			
    			$('#css-mpdplayer').attr(
    				'href',
    				'/css/mpdplayer/'+ $('.player-mpd .now-playing-string').attr('data-hash')
    			);
    			
    			initStuff();
    			
    		});
    	}
    	delete data;
        pollInterval = setTimeout(pollMpdData, 2000);
    });
}

