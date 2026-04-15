(function ($) {
  /**
   * Adds the Pixscribe "Generate Metadata" button to a media view instance.
   */
  function attachPixscribeButton(ViewClass) {
    return ViewClass.extend({
      render: function () {
        ViewClass.__super__.render.apply(this, arguments);

        const $metaPanel = this.$el.find('.details').first();
        if ($metaPanel.length && $metaPanel.find('.pixscribe-generate').length === 0) {
          const $btn = $('<button>')
            .addClass('button pixscribe-generate')
            .text('Run Pixscribe')
            .on('click', () => this.generateMetadata());

          const $container = $('<div>')
            .addClass('pixscribe-generate-container')
            .css({ marginTop: '12px', marginBottom: '12px' })
            .append($btn);

          $metaPanel.append($container);
        }

        return this;
      },

      generateMetadata: function () {
        const id = this.model.get('id');
        const $btn = this.$el.find('.pixscribe-generate');
        $btn.text('Generating...').prop('disabled', true);

        wp.apiRequest({
          path: 'media-meta/v1/generate',
          method: 'POST',
          data: { attachment_id: id },
        })
          .done(() => {
            const existing = {
              alt_text: this.model.get('alt') || '',
              title: this.model.get('title') || '',
              caption: this.model.get('caption') || '',
              description: this.model.get('description') || '',
            };
            this.pollForGeneratedMetadata(id, existing, 0, $btn);
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

      pollForGeneratedMetadata: function (
        attachmentId,
        existingMetadata,
        attempt,
        $btn
      ) {
        const maxAttempts = 30;
        const intervalMs = 2000;

        if (attempt >= maxAttempts) {
          $btn.text('Still processing...').prop('disabled', false);
          return;
        }

        wp.apiRequest({
          path: `media-meta/v1/get?attachment_id=${attachmentId}`,
          method: 'GET',
        })
          .done((response) => {
            const data = response?.data || {};

            if (this.hasMetadataChanged(existingMetadata, data)) {
              this.applyGeneratedMetadata(data);
              $btn.text('Updated').prop('disabled', false);
              setTimeout(() => $btn.text('Run Pixscribe'), 1200);
              return;
            }

            setTimeout(() => {
              this.pollForGeneratedMetadata(
                attachmentId,
                existingMetadata,
                attempt + 1,
                $btn
              );
            }, intervalMs);
          })
          .fail(() => {
            setTimeout(() => {
              this.pollForGeneratedMetadata(
                attachmentId,
                existingMetadata,
                attempt + 1,
                $btn
              );
            }, intervalMs);
          });
      },

      hasMetadataChanged: function (before, after) {
        const normalize = (value) => (value || '').toString().trim();
        return (
          normalize(after.alt_text) !== normalize(before.alt_text) ||
          normalize(after.title) !== normalize(before.title) ||
          normalize(after.caption) !== normalize(before.caption) ||
          normalize(after.description) !== normalize(before.description)
        );
      },

      applyGeneratedMetadata: function (data) {
        const alt = data?.alt_text || '';
        const title = data?.title || '';
        const caption = data?.caption || '';
        const description = data?.description || '';

        this.model.set({
          alt,
          title,
          caption,
          description,
        });

        this.$el.find('.setting[data-setting="alt"] input').val(alt).trigger('change');
        this.$el.find('.setting[data-setting="title"] input').val(title).trigger('change');
        this.$el.find('.setting[data-setting="caption"] textarea').val(caption).trigger('change');
        this.$el.find('.setting[data-setting="description"] textarea').val(description).trigger('change');
      },
    });
  }

  if (wp.media?.view?.Attachment?.Details?.TwoColumn) {
    wp.media.view.Attachment.Details.TwoColumn = attachPixscribeButton(
      wp.media.view.Attachment.Details.TwoColumn
    );
  }

  if (wp.media?.view?.Attachment?.Details) {
    wp.media.view.Attachment.Details = attachPixscribeButton(
      wp.media.view.Attachment.Details
    );
  }
})(jQuery);
