(function ($) {
  /**
   * Adds the Pixscribe "Generate Metadata" button to a media view instance.
   */
  function attachPixscribeButton(ViewClass) {
    return ViewClass.extend({
      render: function () {
        ViewClass.__super__.render.apply(this, arguments);

        const $actions = this.$el.find('.attachment-actions');
        if ($actions.find('.pixscribe-generate').length === 0) {
          const $btn = $('<button>')
            .addClass('button pixscribe-generate')
            .text('Run Pixscribe')
            .on('click', () => this.generateMetadata());
          $actions.append($btn);
        }

        // Auto-poll for newly uploaded images
        this.autoPollIfNeeded();

        return this;
      },

      autoPollIfNeeded: function () {
        const model = this.model;
        const id = model.get('id');
        const mime = model.get('mime') || model.get('type') || '';

        // Only poll for images
        if (!mime.startsWith('image/')) {
          return;
        }

        // Check if metadata is empty (likely just uploaded)
        const hasEmptyMetadata =
          !model.get('alt') &&
          !model.get('title') &&
          !model.get('caption') &&
          !model.get('description');

        // Only auto-poll if metadata is empty and not already polling
        if (
          hasEmptyMetadata &&
          !window.PixscribeMetadataPoller.activePolls[id]
        ) {
          const self = this;
          window.PixscribeMetadataPoller.poll(id, model, null, {
            onSuccess: function () {
              // Refresh the view when metadata is updated
              self.render();
            },
          });
        }
      },

      generateMetadata: function () {
        const id = this.model.get('id');
        const $btn = this.$el.find('.pixscribe-generate');
        const self = this;
        $btn.text('Generating...').prop('disabled', true);

        wp.apiRequest({
          path: 'media-meta/v1/generate',
          method: 'POST',
          data: { attachment_id: id },
        })
          .done(() => {
            window.PixscribeMetadataPoller.poll(id, this.model, $btn, {
              onSuccess: function () {
                // Refresh the view when metadata is updated
                self.render();
              },
            });
          })
          .fail((err) => {
            console.error(err);
            $btn
              .text('Failed')
              .delay(1500)
              .queue(function (next) {
                $(this).text('Run Pixscribe').prop('disabled', false);
                next();
              });
          });
      },
    });
  }

  // Extend the base Attachment view for auto-polling in grid/list views
  wp.media.view.Attachment = wp.media.view.Attachment.extend({
    render: function () {
      wp.media.view.Attachment.__super__.render.apply(this, arguments);
      this.autoPollIfNeeded();
      return this;
    },

    autoPollIfNeeded: function () {
      const model = this.model;
      const id = model.get('id');
      const mime = model.get('mime') || model.get('type') || '';

      // Only poll for images
      if (!mime.startsWith('image/')) {
        return;
      }

      // Check if metadata is empty (likely just uploaded)
      const hasEmptyMetadata =
        !model.get('alt') &&
        !model.get('title') &&
        !model.get('caption') &&
        !model.get('description');

      // Only auto-poll if metadata is empty and not already polling
      if (hasEmptyMetadata && !window.PixscribeMetadataPoller.activePolls[id]) {
        const self = this;
        window.PixscribeMetadataPoller.poll(id, model, null, {
          onSuccess: function () {
            // Refresh the view when metadata is updated
            self.render();
          },
        });
      }
    },
  });

  wp.media.view.Attachment.Details.TwoColumn = attachPixscribeButton(
    wp.media.view.Attachment.Details.TwoColumn
  );
})(jQuery);
