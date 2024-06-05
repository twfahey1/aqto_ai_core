(function ($, Drupal, once) {
  Drupal.behaviors.agentFormToggle = {
    attach: function (context, settings) {

      once('agentFormToggle', '#agent-form-toggle', context).forEach(function (element) {
        $(element).click(function () {
          $('#agent-form-container').toggleClass('hidden');
          saveToggleState();
        });
      });

      var $container;

      once('resizerContainer', '#agent-form-toolbar', context).forEach(function (element) {
        $container = $(element);
        $container.append('<div class="resizer top"></div>');
        $container.append('<div class="resizer bottom"></div>');
        $container.append('<div class="resizer left"></div>');
        $container.append('<div class="resizer right"></div>');
        $container.append('<div class="resizer bottom-right"></div>');
        $container.append('<div class="resizer top-right"></div>');
        $container.append('<div class="resizer bottom-left"></div>');
        $container.append('<div class="resizer top-left"></div>');

        // Restore position and size from local storage
        var savedPosition = JSON.parse(localStorage.getItem('agentFormPosition'));
        if (savedPosition) {
          $container.css(savedPosition);
        }

        // Restore toggle state from local storage
        var isFormOpen = localStorage.getItem('agentFormOpen');
        if (isFormOpen === 'true') {
          $('#agent-form-container').removeClass('hidden');
        } else {
          $('#agent-form-container').addClass('hidden');
        }
      });

      var startX, startY, startWidth, startHeight, startTop, startLeft, isDragging = false;

      function savePosition() {
        var position = {
          width: $container.css('width'),
          height: $container.css('height'),
          top: $container.css('top'),
          left: $container.css('left'),
          bottom: $container.css('bottom'),
          right: $container.css('right'),
          position: 'fixed'
        };
        localStorage.setItem('agentFormPosition', JSON.stringify(position));
      }

      function saveToggleState() {
        var isFormOpen = !$('#agent-form-container').hasClass('hidden');
        localStorage.setItem('agentFormOpen', isFormOpen);
      }

      function initResize(e, direction) {
        e.preventDefault();
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
        if (direction.includes('right')) {
          $container.css('width', startWidth + e.clientX - startX + 'px');
        } 
        if (direction.includes('bottom')) {
          $container.css('height', startHeight + e.clientY - startY + 'px');
        }
        if (direction.includes('left')) {
          $container.css('width', startWidth - (e.clientX - startX) + 'px');
          $container.css('left', startLeft + (e.clientX - startX) + 'px');
        }
        if (direction.includes('top')) {
          $container.css('height', startHeight - (e.clientY - startY) + 'px');
          $container.css('top', startTop + (e.clientY - startY) + 'px');
        }
        savePosition();
      }

      function stopResize() {
        $(document).off('mousemove');
        $(document).off('mouseup');
      }

      function initDrag(e) {
        e.preventDefault();
        startX = e.clientX;
        startY = e.clientY;
        startTop = parseInt(document.defaultView.getComputedStyle($container[0]).top, 10);
        startLeft = parseInt(document.defaultView.getComputedStyle($container[0]).left, 10);
        isDragging = true;
        $(document).mousemove(doDrag);
        $(document).mouseup(stopDrag);
      }

      function doDrag(e) {
        if (isDragging) {
          $container.css('top', startTop + e.clientY - startY + 'px');
          $container.css('left', startLeft + e.clientX - startX + 'px');
          savePosition();
        }
      }

      function stopDrag() {
        isDragging = false;
        $(document).off('mousemove');
        $(document).off('mouseup');
      }

      function resetPosition() {
        $container.css({
          'width': '300px',
          'height': '300px',
          'bottom': '0',
          'right': '0',
          'top': '',
          'left': '',
          'position': 'fixed'
        });
        savePosition();
      }

      once('resizer', '.resizer', context).forEach(function (element) {
        var $resizer = $(element);
        var direction = $resizer.attr('class').split(' ').pop();
        $resizer.on('mousedown', function (e) {
          initResize(e, direction);
        });
      });

      once('draggable', '#agent-form-move', context).forEach(function (element) {
        var $moveHandle = $(element);
        $moveHandle.on('mousedown', initDrag);
        $moveHandle.on('click', function (e) {
          if (e.detail === 3) {
            resetPosition();
          }
        });
      });

    }
  };
})(jQuery, Drupal, once);
