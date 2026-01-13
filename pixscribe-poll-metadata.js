(function ($) {
  /**
   * Polls for metadata updates and refreshes the media view when changes are detected
   */
  window.PixscribeMetadataPoller = {
    activePolls: {}, // Track active polls to avoid duplicates

    poll: function (attachmentId, model, $btn, options) {
      options = options || {};
      const maxAttempts = options.maxAttempts || 60; // 60 attempts = 2 minutes max
      const interval = options.interval || 2000; // Check every 2 seconds

      // Prevent duplicate polling for the same attachment
      if (this.activePolls[attachmentId]) {
        return;
      }
      this.activePolls[attachmentId] = true;

      let attempts = 0;

      const initialData = {
        alt_text: model.get('alt'),
        title: model.get('title'),
        caption: model.get('caption'),
        description: model.get('description'),
      };

      const cleanup = () => {
        delete window.PixscribeMetadataPoller.activePolls[attachmentId];
      };

      const checkMetadata = () => {
        attempts++;

        wp.apiRequest({
          path: `media-meta/v1/get?attachment_id=${attachmentId}`,
          method: 'GET',
        })
          .done((response) => {
            if (response.success && response.data) {
              const newData = response.data;
              // Normalize empty strings and null/undefined for comparison
              const normalize = (val) => (val || '').toString().trim();
              const hasChanges =
                normalize(newData.alt_text) !==
                  normalize(initialData.alt_text) ||
                normalize(newData.title) !== normalize(initialData.title) ||
                normalize(newData.caption) !== normalize(initialData.caption) ||
                normalize(newData.description) !==
                  normalize(initialData.description);

              if (hasChanges) {
                // Update the model
                if (newData.alt_text !== undefined)
                  model.set('alt', newData.alt_text);
                if (newData.title !== undefined)
                  model.set('title', newData.title);
                if (newData.caption !== undefined)
                  model.set('caption', newData.caption);
                if (newData.description !== undefined)
                  model.set('description', newData.description);

                // Trigger change event to update UI
                model.trigger('change');

                // Call success callback if provided
                if (options.onSuccess) {
                  options.onSuccess(newData);
                }

                cleanup();

                if ($btn && $btn.length) {
                  $btn
                    .text('Complete!')
                    .delay(1500)
                    .queue(function (next) {
                      $(this).text('Run Pixscribe').prop('disabled', false);
                      next();
                    });
                }
                return;
              }
            }

            // Continue polling if no changes yet and haven't exceeded max attempts
            if (attempts < maxAttempts) {
              setTimeout(checkMetadata, interval);
            } else {
              cleanup();
              if (options.onTimeout) {
                options.onTimeout();
              }
              if ($btn && $btn.length) {
                $btn
                  .text('Timeout')
                  .delay(1500)
                  .queue(function (next) {
                    $(this).text('Run Pixscribe').prop('disabled', false);
                    next();
                  });
              }
            }
          })
          .fail(() => {
            // Continue polling on error (might be transient)
            if (attempts < maxAttempts) {
              setTimeout(checkMetadata, interval);
            } else {
              cleanup();
              if (options.onError) {
                options.onError();
              }
              if ($btn && $btn.length) {
                $btn
                  .text('Error')
                  .delay(1500)
                  .queue(function (next) {
                    $(this).text('Run Pixscribe').prop('disabled', false);
                    next();
                  });
              }
            }
          });
      };

      // Start polling after a short delay
      setTimeout(checkMetadata, interval);
    },
  };
})(jQuery);
