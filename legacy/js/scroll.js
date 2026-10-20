(function () {
  var scrollCount = 0; // Số lần đã scroll
  var totalItems = 0; // Tổng số items
  var itemHeight = 0; // Chiều cao của một item

  function initScroller() {
    var scroll = $("div.scroll");
    var firstItem = scroll.find(".product-sidebar-item:first");
    itemHeight = firstItem.outerHeight();
    totalItems = scroll.find(".product-sidebar-item").length;
    scrollCount = 0;

    continueScroller();
  }

  function continueScroller() {
    var scroll = $("div.scroll");

    var newTop = -(scrollCount + 1) * itemHeight;

    scroll.animate(
      {
        top: [newTop, "linear"],
      },
      1000,
      function () {
        scrollCount++;

        // Nếu đã scrolled đến phần tử cuối (duplicate), reset
        if (scrollCount >= totalItems - 1) {
          scroll.css("top", 0);
          scrollCount = 0;
        }

        // Chờ 1s rồi tiếp tục
        setTimeout(function () {
          continueScroller();
        }, 1000);
      },
    );
  }

  initScroller();
})();
