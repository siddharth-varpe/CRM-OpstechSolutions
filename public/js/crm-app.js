/**
 * ENTERPRISES CRM - UNIVERSAL CLIENT APPLICATION JAVASCRIPT
 * jQuery & AJAX Framework integration
 */

(function($) {
  'use strict';

  // CSRF Token setup for all jQuery AJAX requests
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
      'Accept': 'application/json'
    }
  });

  // Re-render Lucide icons on DOM ready and after dynamic DOM changes
  $(document).ready(function() {
    if (typeof lucide !== 'undefined') {
      lucide.createIcons();
    }
  });

  // Global Toast Notification System
  window.showToast = function(message, type) {
    type = type || 'info'; // 'success', 'danger', 'warning', 'info'
    const colors = {
      success: 'bg-emerald-600 text-white',
      danger: 'bg-rose-600 text-white',
      warning: 'bg-amber-500 text-white',
      info: 'bg-sky-600 text-white'
    };
    const iconNames = {
      success: 'check-circle-2',
      danger: 'alert-triangle',
      warning: 'alert-circle',
      info: 'info'
    };

    let $container = $('#toast-container');
    if ($container.length === 0) {
      $container = $('<div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none"></div>');
      $('body').append($container);
    }

    const toastId = 'toast-' + Date.now();
    const $toast = $(`
      <div id="${toastId}" class="pointer-events-auto flex items-center p-3.5 rounded-lg shadow-lg ${colors[type] || colors.info} transform transition-all duration-200 translate-y-2 opacity-0">
        <i data-lucide="${iconNames[type] || 'info'}" class="w-5 h-5 mr-3 shrink-0"></i>
        <div class="text-sm font-medium flex-1">${message}</div>
        <button type="button" class="ml-2 text-white/80 hover:text-white" onclick="$('#${toastId}').remove()">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>
    `);

    $container.append($toast);
    if (typeof lucide !== 'undefined') {
      lucide.createIcons();
    }

    setTimeout(() => {
      $toast.removeClass('translate-y-2 opacity-0').addClass('translate-y-0 opacity-100');
    }, 10);

    setTimeout(() => {
      $toast.removeClass('translate-y-0 opacity-100').addClass('translate-y-2 opacity-0');
      setTimeout(() => $toast.remove(), 250);
    }, 4000);
  };

  // Modal Dialog Handlers
  window.openModal = function(modalId) {
    if (!modalId) return;
    modalId = modalId.replace(/^#/, '');
    const $modal = $('#' + modalId);
    $modal.removeClass('hidden').addClass('flex');
    $('body').addClass('overflow-hidden');
    if (typeof lucide !== 'undefined') {
      lucide.createIcons();
    }
  };

  window.closeModal = function(modalId) {
    if (!modalId) return;
    modalId = modalId.replace(/^#/, '');
    const $modal = $('#' + modalId);
    $modal.addClass('hidden').removeClass('flex');
    $('body').removeClass('overflow-hidden');
  };

  // Currency & Number Formatter (INR ₹)
  window.formatINR = function(val) {
    const num = parseFloat(val) || 0;
    return '₹' + num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  };

  // Universal CRM Object
  const CRM = {
    openModal: function(modalId) {
      window.openModal(modalId);
    },
    closeModal: function(modalId) {
      window.closeModal(modalId);
    },
    toast: function(message, type) {
      window.showToast(message, type);
    },
    showToast: function(message, type) {
      window.showToast(message, type);
    },
    formatINR: function(val) {
      return window.formatINR(val);
    },
    handleAjaxForm: function(selector, successCallback, errorCallback) {
      $(document).off('submit', selector).on('submit', selector, function(e) {
        e.preventDefault();
        const $form = $(this);
        const $submitBtn = $form.find('[type="submit"]');
        const originalText = $submitBtn.html();
        const action = $form.attr('action');
        const method = ($form.attr('method') || 'POST').toUpperCase();

        $form.find('.form-error').remove();
        $form.find('.border-rose-500').removeClass('border-rose-500');
        $submitBtn.prop('disabled', true).html('<span class="inline-block animate-spin mr-1.5">⟳</span> Processing...');

        const formData = new FormData(this);

        $.ajax({
          url: action,
          method: method,
          data: formData,
          processData: false,
          contentType: false,
          success: function(response) {
            $submitBtn.prop('disabled', false).html(originalText);
            if (typeof successCallback === 'function') {
              successCallback(response, $form);
            } else {
              if (response.message) {
                CRM.toast(response.message, 'success');
              }
              if (response.redirect) {
                setTimeout(() => { window.location.href = response.redirect; }, 500);
              } else if (response.reload) {
                setTimeout(() => { window.location.reload(); }, 500);
              }
            }
          },
          error: function(xhr) {
            $submitBtn.prop('disabled', false).html(originalText);
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
              const errors = xhr.responseJSON.errors;
              let firstMsg = '';
              $.each(errors, function(field, messages) {
                if (!firstMsg) firstMsg = messages[0];
                const $input = $form.find(`[name="${field}"]`);
                if ($input.length) {
                  $input.addClass('border-rose-500');
                  $input.after(`<p class="form-error text-xs text-rose-500 mt-1 font-medium">${messages[0]}</p>`);
                }
              });
              CRM.toast(firstMsg || 'Please review validation errors on the form.', 'error');
            } else {
              const err = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'An unexpected error occurred. Please try again.';
              CRM.toast(err, 'error');
            }
            if (typeof errorCallback === 'function') {
              errorCallback(xhr, $form);
            }
          }
        });
      });
    }
  };

  window.CRM = CRM;

  // Backdrop click to close modals
  $(document).on('click', '.modal-backdrop', function(e) {
    if ($(e.target).hasClass('modal-backdrop')) {
      const modalId = $(this).attr('id');
      if (modalId) {
        window.closeModal(modalId);
      }
    }
  });

  // ESC key to close open modals
  $(document).on('keydown', function(e) {
    if (e.key === 'Escape') {
      $('.modal-backdrop:not(.hidden)').each(function() {
        const id = $(this).attr('id');
        if (id) window.closeModal(id);
      });
    }
  });

  // Generic AJAX Form Submitter with automatic error rendering for data-ajax="true"
  $(document).on('submit', 'form[data-ajax="true"]', function(e) {
    e.preventDefault();
    const $form = $(this);
    const $submitBtn = $form.find('[type="submit"]');
    const originalText = $submitBtn.html();
    const action = $form.attr('action');
    const method = $form.attr('method') || 'POST';

    // Clear previous errors
    $form.find('.form-error').remove();
    $form.find('.border-rose-500').removeClass('border-rose-500');

    $submitBtn.prop('disabled', true).html('<span class="inline-block animate-spin mr-1">⟳</span> Saving...');

    $.ajax({
      url: action,
      method: method,
      data: new FormData(this),
      processData: false,
      contentType: false,
      success: function(response) {
        $submitBtn.prop('disabled', false).html(originalText);
        if (response.message) {
          window.showToast(response.message, 'success');
        }
        if (response.redirect) {
          setTimeout(() => { window.location.href = response.redirect; }, 400);
        } else if (response.reload) {
          setTimeout(() => { window.location.reload(); }, 400);
        } else {
          const modalId = $form.closest('.modal-container, .modal-backdrop').attr('id');
          if (modalId) window.closeModal(modalId);
          if (typeof window.onAjaxSuccess === 'function') {
            window.onAjaxSuccess(response, $form);
          }
        }
      },
      error: function(xhr) {
        $submitBtn.prop('disabled', false).html(originalText);
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          const errors = xhr.responseJSON.errors;
          $.each(errors, function(field, messages) {
            const $input = $form.find(`[name="${field}"]`);
            $input.addClass('border-rose-500');
            $input.after(`<p class="form-error text-xs text-rose-500 mt-1">${messages[0]}</p>`);
          });
          window.showToast('Please check form validation errors', 'danger');
        } else {
          const err = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'An unexpected error occurred';
          window.showToast(err, 'danger');
        }
      }
    });
  });

})(jQuery);
