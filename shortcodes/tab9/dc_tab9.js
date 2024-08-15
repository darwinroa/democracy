jQuery(document).ready(function($) {
  $(".select-selected").click(function() {
      $(this).toggleClass('active');
      $(this).next(".select-items").toggle();
  });

  $(".select-item").click(function() {
      var value = $(this).attr("data-value");
      var text = $(this).text();
      $(".select-selected, .filter-title #title").text(text);
      $(".filter-content").addClass('hide');
      $("#" + value ).removeClass('hide');
      $(".select-items").hide();
  });

  $(document).click(function(e) {
      if (!$(e.target).closest(".custom-select").length) {
          $(".select-items").hide();
      }
  });
});