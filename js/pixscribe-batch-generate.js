document.addEventListener('DOMContentLoaded', function () {
  const batchButton = document.getElementById('pixscribe-batch-button');
  const statusDiv = document.getElementById('pixscribe-batch-status');
  const confirmDiv = document.getElementById('pixscribe-batch-confirm');

  if (!batchButton) {
    console.error('Batch button not found in DOM');
    return;
  }

  let isProcessing = false;

  batchButton.addEventListener('click', function () {
    if (isProcessing) return;

    // Show confirmation
    if (confirmDiv) {
      confirmDiv.innerHTML = `
        <div style="padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; margin-bottom: 10px;">
          <p><strong>Are you sure?</strong></p>
          <p>This will process all media files missing alt text. This may take several minutes.</p>
          <button id="pixscribe-confirm-yes" class="button button-primary" style="margin-right: 10px;">Yes, Process All</button>
          <button id="pixscribe-confirm-no" class="button">Cancel</button>
        </div>
      `;

      const confirmYesBtn = document.getElementById('pixscribe-confirm-yes');
      const confirmNoBtn = document.getElementById('pixscribe-confirm-no');

      confirmYesBtn.addEventListener('click', function () {
        confirmDiv.innerHTML = '';
        startBatchProcess();
      });

      confirmNoBtn.addEventListener('click', function () {
        confirmDiv.innerHTML = '';
      });

      return;
    }

    // Fallback if no confirm div
    startBatchProcess();
  });

  function startBatchProcess() {
    isProcessing = true;
    batchButton.disabled = true;

    statusDiv.style.display = 'block';

    if (statusDiv) {
      statusDiv.textContent = 'Fetching attachments...';
    }

    fetch(pixscribeBatch.ajaxUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({
        action: 'pixscribe_batch_get_attachments',
        nonce: pixscribeBatch.nonce,
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((data) => {
        if (data.success && data.data.total > 0) {
          processNextAttachment(
            data.data.attachments,
            0,
            [],
            batchButton
          );
        } else {
          if (statusDiv) {
            statusDiv.textContent = 'No media files found missing alt text.';
          }
          isProcessing = false;
          batchButton.disabled = false;
        }
      })
      .catch((error) => {
        if (statusDiv) {
          statusDiv.textContent = 'Failed to fetch attachments: ' + error;
        }
        isProcessing = false;
        batchButton.disabled = false;
      });
  }

  function processNextAttachment(
    attachments,
    index,
    results,
    button
  ) {
    console.log('Processing attachment', index, 'of', attachments.length);

    if (index >= attachments.length) {
      const successful = results.filter((r) => r.success).length;
      const failed = results.filter((r) => !r.success).length;

      if (statusDiv) {
        statusDiv.innerHTML = `<strong>Batch complete!</strong><br>Successful: ${successful}<br>Failed: ${failed}`;
      }

      isProcessing = false;
      button.disabled = false;
      button.textContent = 'Process Missing Alt Text';
      return;
    }

    const attachment = attachments[index];
    const progress = `Processing (${index + 1}/${attachments.length})...`;
    button.textContent = progress;

    if (statusDiv) {
      statusDiv.textContent = progress;
    }

    fetch(pixscribeBatch.ajaxUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({
        action: 'pixscribe_batch_process_single',
        nonce: pixscribeBatch.nonce,
        attachment_id: attachment.id,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        console.log('Processed attachment:', attachment.id, data);

        results.push({
          id: attachment.id,
          title: attachment.title,
          success: data.success,
          message: data.success
            ? data.data.message
            : data.data,
        });

        setTimeout(() => {
          processNextAttachment(
            attachments,
            index + 1,
            results,
            button
          );
        }, 500);
      })
      .catch((error) => {
        console.error('Process error:', error);

        results.push({
          id: attachment.id,
          title: attachment.title,
          success: false,
          message: 'Request failed: ' + error,
        });

        setTimeout(() => {
          processNextAttachment(
            attachments,
            index + 1,
            results,
            button
          );
        }, 500);
      });
  }
});