$js('jquery',function(){
	
	var menuMap = {
		'getting-started':'getting-started',
		'api-documentation':'documentation',
		'dynamic-database':'documentation',
	};
	
	var location = document.location.pathname;
	location = decodeURIComponent(location.substr(1));
	var loc = location;

	if(menuMap[loc]){
		loc = menuMap[loc];
	}
	var selectorMenu = 'body>header>nav.main>ul>li:has(>a[href="'+loc+'"])';
	selectorMenu += ',body>footer a[href="'+loc+'"]';
	$(selectorMenu).addClass('active');
	
	$(window).on('unload',function(){
		$('main').css('opacity',0.5);
	});
	
	var nav = $('body>header'), fixed = false, navTop;
	var fixNavMenu = function(){
		nav.addClass('f-nav');
		fixed = true;
	};
	var unFixNavMenu = function(){
		fixed = false;
		nav.removeClass('f-nav');
	};
	var adjustMenu = function(reload){
		if(reload)
			unFixNavMenu();
		if(!fixed)
			navTop = nav.offset().top;
		if($(this).scrollTop()>navTop)
			fixNavMenu();
		else
			unFixNavMenu();
    };
    adjustMenu();
    $(window).scroll(adjustMenu);
    $(window).resize(function(){
		if(fixed) adjustMenu(true);
	});
	
	//$.get('visits-counter');
	
	var h = document.location.hash;
	if(h.substr(0,1)=='#')
		h = h.substr(1);
	if(h=='metamorphosis'){
		$js('js/freeow',function(){
			$('body').prepend('<div id="freeow" class="freeow freeow-top"></div>');
			$("#freeow").freeow('Surikat is now RedCat', 'With equal agility than before Surikat has metamorphosed in RedCat', {
				classes: ['smokey'],
				autoHide: true,
				autoHideDelay:10000
			});
		});
	}
});
$js(true,['jquery','js/jquery.scrollUp'],function(){
	$.scrollUp({
		scrollName: 'scrollUp', // Element ID
		topDistance: '300', // Distance from top before showing element (px)
		topSpeed: 300, // Speed back to top (ms)
		animation: 'fade', // Fade, slide, none
		animationInSpeed: 200, // Animation in speed (ms)
		animationOutSpeed: 200, // Animation out speed (ms)
		scrollText: '', // Text for element
		activeOverlay: false, // Set CSS color to display scrollUp active point, e.g '#00FFFF'
	});
});