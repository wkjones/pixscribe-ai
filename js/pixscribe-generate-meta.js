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
            $btn
              .text('Generating...')
              .delay(1500)
              .queue(function (next) {
                $(this).text('Run Pixscribe').prop('disabled', false);
                next();
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

  wp.media.view.Attachment.Details.TwoColumn = attachPixscribeButton(
    wp.media.view.Attachment.Details.TwoColumn
  );
})(jQuery);
