$(document).ready(function(){
	if($('.staff-detail__map').length){
		var htmlMap = $('.staff-detail__map').detach();
		$('.staff-detail__map-wrapper').html(htmlMap);
	}

    $('.staff-epilog .js-load-staff-schedule').iAppear(function(){
        var $this = $(this);
        if(!$this.hasClass('clicked')){
            $this.addClass('clicked');
            $this.addClass('loading-state');
    
            $.ajax({
                type: 'POST',
                data: {
                    BLOCK: 'staff-schedule',
                    AJAX_REQUEST: 'Y',
                    ajax_get: 'Y', 
                    site_id: $this.attr('data-site_id'),
                },
                success: function(html){
                    $this.html(html);
                },
                complete: function(){
                    $this.removeClass('loading-state');
                }
            });
        }
    });
});

$(document).on('click', '.staff-detail .show_on_map > span', function(){
    var $map = $('.staff-detail__map-wrapper');
    if($map.length){
        var animationTime = 200;
        var arCoordinates = $(this).data('coordinates').split(',');
        var countMapPoint = $(this).closest('.staff-detail__top-property__addresses').find('.show_on_map').length;
        var scale = $(this).data('scale');
        if (typeof scale === 'undefined' || scale < 1 || scale > 18) {
            scale = 17;
        }

        if(!$map.is(':visible') || countMapPoint > 1){
            if(typeof map !== 'undefined'){
				if (typeof map === 'object' && map !== null && 'setCenter' in map) {
                    if ($('.bx-google-map').length) {
                        map.setCenter({ lat: +arCoordinates[0], lng: +arCoordinates[1] });
                        map.setZoom(scale);
                    }
                    else {
                        map.setCenter([arCoordinates[0], arCoordinates[1]], scale);
                    }
                }
			}

            $map.show();
            $map.find('.staff-detail__map').slideDown(animationTime, function(){
                $map.find('.staff-detail__map__close').show();
            });
        }
        else{
            setTimeout(function(){
                $map.hide();
            }, animationTime);

            $map.find('.staff-detail__map__close').hide();
            $map.find('.staff-detail__map').slideUp(animationTime);
        }
    }
});

$(document).on('click', '.staff-detail__map__close', function(){
    var $map = $('.staff-detail__map-wrapper');
    if($map.length){
        var animationTime = 200;
        
        setTimeout(function(){
            $map.hide();
        }, animationTime);
    
        $map.find('.staff-detail__map__close').hide();
        $map.find('.staff-detail__map').slideUp(animationTime);
    }
});

$(document).on('click', '.staff-schedule__top .arrow_link', function(e){
    var $this = $(this);
    e.preventDefault();

    $this.closest('.js-load-staff-schedule').addClass('loading-state');
    $.ajax({
        type: 'POST',
        data: {
            BLOCK: 'staff-schedule',
            AJAX_REQUEST: 'Y',
            ajax_get: 'Y', 
            site_id: $this.closest('.js-load-staff-schedule').attr('data-site_id'),
            dateto: $this.attr('data-dateto'),
            datefrom: $this.attr('data-datefrom'),
        },
        success: function(html){
            $this.closest('.js-load-staff-schedule').html(html);
        },
        complete: function(){
            $this.closest('.js-load-staff-schedule').removeClass('loading-state');
        }
    });
});

$(document).on('click', '.staff-schedule .show_on_map > span', function(){
    var $map = $('.staff-schedule__map-wrapper');
    if($map.length){
        var arCoordinates = $(this).data('coordinates').split(',');
        var scale = $(this).data('scale');
        if (typeof scale === 'undefined' || scale < 1 || scale > 18) {
            scale = 17;
        }

        scrollToBlock($map);

        if (typeof map === 'object' && map !== null && 'setCenter' in map) {
            if ($('.bx-google-map').length) {
                map.setCenter({ lat: +arCoordinates[0], lng: +arCoordinates[1] });
                map.setZoom(scale);
            }
            else {
                map.setCenter([arCoordinates[0], arCoordinates[1]], scale);
            }
        }
    }
});