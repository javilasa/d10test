(function ($, Drupal) {
    Drupal.behaviors.miTemaMenu = {
      attach: function (context, settings) {
        $('ul.menu li').on('touchstart', function (e) {
          const submenu = $(this).find('ul');
          if (submenu.length) {
            e.preventDefault();
            submenu.toggle();
          }
        });
      },
    };
  })(jQuery, Drupal);
  