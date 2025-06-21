jQuery(document).ready(function ($) {
  // Modal elements
  const modal = $("#atf-details-modal");
  const modalContent = $("#atf-modal-body");
  const closeBtn = $(".atf-close");

  // Open modal when clicking on a "view details" button
  $(".view-details").on("click", function () {
    const id = $(this).data("id");

    // Show loading indicator
    modalContent.html('<div class="spinner is-active"></div>');
    modal.show();

    // Fetch details via AJAX
    $.ajax({
      type: "POST",
      url: ajaxurl,
      data: {
        action: "atf_get_details",
        id: id,
        security: atfAdmin.nonce,
      },
      success: function (response) {
        if (response.success) {
          modalContent.html(response.data);
        } else {
          modalContent.html("<p>" + response.data + "</p>");
        }
      },
      error: function () {
        modalContent.html(
          "<p>Unable to load details. Please try again later.</p>"
        );
      },
    });
  });

  // Close modal on button click
  closeBtn.on("click", function () {
    modal.hide();
  });

  // Close modal when clicking outside the modal content
  $(window).on("click", function (e) {
    if ($(e.target).is(modal)) {
      modal.hide();
    }
  });
});
