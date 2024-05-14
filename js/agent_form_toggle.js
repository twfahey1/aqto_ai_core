(function ($, Drupal, once) {
  Drupal.behaviors.agentFormToggle = {
    attach: function (context, settings) {
        console.log('foo');
      once('agentFormToggle', '#agent-form-toggle', context).forEach(function (element) {
        $(element).click(function () {
          $('#agent-form-container').toggleClass('hidden');
        });
      });
    }
  };
})(jQuery, Drupal, once);
