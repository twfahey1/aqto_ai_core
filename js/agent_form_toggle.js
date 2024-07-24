(function ($, Drupal, once) {
  Drupal.behaviors.agentFormToggle = {
    attach: function (context, settings) {

      once('agentFormToggle', '#agent-form-toggle', context).forEach(function (element) {
        $(element).on('click', function () {
          var $container = $('#agent-form-container');
          $container.toggleClass('hidden');
          toggleSize();
        });
      });

      var $container;
      var defaultOpenHeight = '300px'; // Set your default open height here
      var defaultWidth = '300px'; // Set your default width here

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
        } else {
          $container.css({
            width: defaultWidth,
            height: defaultOpenHeight,
            bottom: '0',
            right: '0',
            position: 'fixed'
          });
        }

        // Restore toggle state and height from local storage
        var isFormOpen = localStorage.getItem('agentFormOpen');
        if (isFormOpen === 'true') {
          $('#agent-form-container').removeClass('hidden');
          var openHeight = localStorage.getItem('agentFormOpenHeight');
          if (openHeight) {
            $container.css('height', openHeight);
          } else {
            $container.css('height', defaultOpenHeight);
          }

          var openWidth = localStorage.getItem('agentFormOpenWidth');
          if (openWidth) {
            $container.css('width', openWidth);
          } else {
            $container.css('width', defaultWidth);
          }
        } else {
          $('#agent-form-container').addClass('hidden');
          var closedHeight = localStorage.getItem('agentFormClosedHeight');
          if (closedHeight) {
            $container.css('height', closedHeight);
          }

          var closedWidth = localStorage.getItem('agentFormClosedWidth');
          if (closedWidth) {
            $container.css('width', closedWidth);
          }
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

        if ($('#agent-form-container').hasClass('hidden')) {
          localStorage.setItem('agentFormClosedHeight', $container.css('height'));
          localStorage.setItem('agentFormClosedWidth', $container.css('width'));
        } else {
          localStorage.setItem('agentFormOpenHeight', $container.css('height'));
          localStorage.setItem('agentFormOpenWidth', $container.css('width'));
        }
      }

      function saveToggleState() {
        var isFormOpen = !$('#agent-form-container').hasClass('hidden');
        localStorage.setItem('agentFormOpen', isFormOpen);
      }

      function toggleSize() {
        var $container = $('#agent-form-toolbar');
        if ($('#agent-form-container').hasClass('hidden')) {
          var closedHeight = localStorage.getItem('agentFormClosedHeight');
          var closedWidth = localStorage.getItem('agentFormClosedWidth');
          $container.css({
            height: closedHeight ? closedHeight : '50px', // Default closed height
            width: closedWidth ? closedWidth : defaultWidth
          });
        } else {
          var openHeight = localStorage.getItem('agentFormOpenHeight');
          var openWidth = localStorage.getItem('agentFormOpenWidth');
          $container.css({
            height: openHeight ? openHeight : defaultOpenHeight,
            width: openWidth ? openWidth : defaultWidth
          });
        }
        saveToggleState();
        savePosition();
      }

      function initResize(e, direction) {
        e.preventDefault();
        startX = e.clientX || e.touches[0].clientX;
        startY = e.clientY || e.touches[0].clientY;
        startWidth = parseInt(document.defaultView.getComputedStyle($container[0]).width, 10);
        startHeight = parseInt(document.defaultView.getComputedStyle($container[0]).height, 10);
        startTop = parseInt(document.defaultView.getComputedStyle($container[0]).top, 10);
        startLeft = parseInt(document.defaultView.getComputedStyle($container[0]).left, 10);
        $(document).on('mousemove touchmove', doResize.bind(null, direction));
        $(document).on('mouseup touchend', stopResize);
      }

      function doResize(direction, e) {
        var clientX = e.clientX || e.touches[0].clientX;
        var clientY = e.clientY || e.touches[0].clientY;
        if (direction.includes('right')) {
          $container.css('width', startWidth + clientX - startX + 'px');
        }
        if (direction.includes('bottom')) {
          $container.css('height', startHeight + clientY - startY + 'px');
        }
        if (direction.includes('left')) {
          $container.css('width', startWidth - (clientX - startX) + 'px');
          $container.css('left', startLeft + (clientX - startX) + 'px');
        }
        if (direction.includes('top')) {
          $container.css('height', startHeight - (clientY - startY) + 'px');
          $container.css('top', startTop + (clientY - startY) + 'px');
        }
        savePosition();
      }

      function stopResize() {
        $(document).off('mousemove touchmove');
        $(document).off('mouseup touchend');
      }

      function initDrag(e) {
        e.preventDefault();
        startX = e.clientX || e.touches[0].clientX;
        startY = e.clientY || e.touches[0].clientY;
        startTop = parseInt(document.defaultView.getComputedStyle($container[0]).top, 10);
        startLeft = parseInt(document.defaultView.getComputedStyle($container[0]).left, 10);
        isDragging = true;
        $(document).on('mousemove touchmove', doDrag);
        $(document).on('mouseup touchend', stopDrag);
      }

      function doDrag(e) {
        var clientX = e.clientX || e.touches[0].clientX;
        var clientY = e.clientY || e.touches[0].clientY;
        if (isDragging) {
          $container.css('top', startTop + clientY - startY + 'px');
          $container.css('left', startLeft + clientX - startX + 'px');
          savePosition();
        }
      }

      function stopDrag() {
        isDragging = false;
        $(document).off('mousemove touchmove');
        $(document).off('mouseup touchend');
      }

      function resetPosition() {
        $container.css({
          width: defaultWidth,
          height: defaultOpenHeight,
          bottom: '0',
          right: '0',
          top: '',
          left: '',
          position: 'fixed'
        });
        savePosition();
      }

      once('resizer', '.resizer', context).forEach(function (element) {
        var $resizer = $(element);
        var direction = $resizer.attr('class').split(' ').pop();
        $resizer.on('mousedown touchstart', function (e) {
          initResize(e, direction);
        });
      });

      once('draggable', '.move', context).forEach(function (element) {
        var $moveHandle = $(element);
        $moveHandle.on('mousedown touchstart', initDrag);
        $moveHandle.on('click', function (e) {
          if (e.detail === 3) {
            resetPosition();
          }
        });
      });

      // Add event listener for the reset button
      once('resetPosition', '#agent-form-reset', context).forEach(function (element) {
        var $resetButton = $(element);
        $resetButton.on('click', function () {
          resetPosition();
        });
      });

    }
  };
})(jQuery, Drupal, once);
