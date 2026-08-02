(function(){
    var toggleButton = document.querySelector('.dashboard-menu-toggle');
    var sidebar;
    var overlay = document.querySelector('.dashboard-sidebar-overlay');
    var sidebarId;
    var menuLinks;
    var index;

    if(!toggleButton || !overlay){
        return;
    }

    sidebarId = toggleButton.getAttribute('aria-controls');
    sidebar = document.getElementById(sidebarId);

    if(!sidebar){
        return;
    }

    function openMenu(){
        sidebar.classList.add('open');
        overlay.classList.add('active');
        document.body.classList.add('dashboard-menu-open');
        toggleButton.setAttribute('aria-expanded', 'true');
        overlay.setAttribute('aria-hidden', 'false');
    }

    function closeMenu(){
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        document.body.classList.remove('dashboard-menu-open');
        toggleButton.setAttribute('aria-expanded', 'false');
        overlay.setAttribute('aria-hidden', 'true');
    }

    toggleButton.addEventListener('click', function(){
        if(sidebar.classList.contains('open')){
            closeMenu();
        }else{
            openMenu();
        }
    });

    overlay.addEventListener('click', closeMenu);

    document.addEventListener('keydown', function(event){
        event = event || window.event;

        if((event.key === 'Escape' || event.keyCode === 27) && sidebar.classList.contains('open')){
            closeMenu();
        }
    });

    menuLinks = sidebar.getElementsByTagName('a');

    for(index = 0; index < menuLinks.length; index++){
        menuLinks[index].addEventListener('click', function(){
            if(window.innerWidth < 768){
                closeMenu();
            }
        });
    }

    window.addEventListener('resize', function(){
        if(window.innerWidth >= 768){
            closeMenu();
        }
    });
})();
