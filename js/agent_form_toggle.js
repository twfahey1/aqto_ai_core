(function ($, Drupal, once) {
  Drupal.behaviors.agentFormToggle = {
    attach: function (context, settings) {
      once('agentFormToggle', '#agent-form-toggle', context).forEach(function (element) {
        $(element).click(function () {
          $('#agent-form-container').toggleClass('hidden');
        });
      });

      // Add resizers to the modal
      var $container = $('#agent-form-container');
      $container.append('<div class="resizer top"></div>');
      $container.append('<div class="resizer bottom"></div>');
      $container.append('<div class="resizer left"></div>');
      $container.append('<div class="resizer right"></div>');
      $container.append('<div class="resizer bottom-right"></div>');

      var startX, startY, startWidth, startHeight, startTop, startLeft;

      function initResize(e, direction) {
        startX = e.clientX;
        startY = e.clientY;
        startWidth = parseInt(document.defaultView.getComputedStyle($container[0]).width, 10);
        startHeight = parseInt(document.defaultView.getComputedStyle($container[0]).height, 10);
        startTop = parseInt(document.defaultView.getComputedStyle($container[0]).top, 10);
        startLeft = parseInt(document.defaultView.getComputedStyle($container[0]).left, 10);
        $(document).mousemove(doResize.bind(null, direction));
        $(document).mouseup(stopResize);
      }

      function doResize(direction, e) {
        if (direction === 'right') {
          $container.css('width', startWidth + e.clientX - startX + 'px');
        } else if (direction === 'bottom') {
          $container.css('height', startHeight + e.clientY - startY + 'px');
        } else if (direction === 'left') {
          $container.css('width', startWidth - (e.clientX - startX) + 'px');
          $container.css('left', startLeft + (e.clientX - startX) + 'px');
        } else if (direction === 'top') {
          $container.css('height', startHeight - (e.clientY - startY) + 'px');
          $container.css('top', startTop + (e.clientY - startY) + 'px');
        } else if (direction === 'bottom-right') {
          $container.css('width', startWidth + e.clientX - startX + 'px');
          $container.css('height', startHeight + e.clientY - startY + 'px');
        }
      }

      function stopResize() {
        $(document).off('mousemove');
        $(document).off('mouseup');
      }

      $('.resizer.right').on('mousedown', function (e) {
        initResize(e, 'right');
      });

      $('.resizer.bottom').on('mousedown', function (e) {
        initResize(e, 'bottom');
      });

      $('.resizer.left').on('mousedown', function (e) {
        initResize(e, 'left');
      });

      $('.resizer.top').on('mousedown', function (e) {
        initResize(e, 'top');
      });

      $('.resizer.bottom-right').on('mousedown', function (e) {
        initResize(e, 'bottom-right');
      });
    }
  };
})(jQuery, Drupal, once);
