(function($){
    $(function(){
        $('body').on('click', '.kb-add-to-cart', function(){
            var $this = $(this);
            $this.addClass('loading').removeClass('not-loading');
            $.ajax({
                type: "POST",
                url: $(this).data('url'),
                async: false,
                dataType: 'json',
                data: {
                    'action': 'kbAddToCartAction',
                    'id' : $this.data('id')
                }
            }).done(function(data) {
               if (undefined !== data['msg']) {
                   alert(data['msg']);
               } else {
                   $this.after(data['button']);
                   $this.remove();
                   var $cart = $('#cart.kb-amz-cart').after(data.cart);
                   $cart.remove();
               }
               $this.removeClass('loading').addClass('not-loading');
            }).error(function(){
                alert('Server busy. Please try again.');
                $this.removeClass('loading').addClass('not-loading');
            });
        });
        
        $('body').on('click', '.kb-mini-cart-item-remove', function(){
            var $this = $(this);
            $.ajax({
                type: "POST",
                url: $(this).data('url'),
                async: true,
                dataType: 'json',
                data: {
                    'action': 'kbRemoveFromCartAction',
                    'id' : $this.data('id')
                }
            }).done(function(data) {
               if (undefined !== data['msg']) {
                   alert(data['msg']);
               } else {
                   $this.after(data['button']);
                   $this.remove();
                   var $cart = $('#cart.kb-amz-cart').after(data.cart);
                   $cart.remove();
               }
            });
        });
        
        // Cart
        $('body').on('click', '.kb-amz-cart .cart-heading', function(e) {
            $(this).closest('.kb-amz-cart').toggleClass('active');
        });
        
        $('body').on('mouseover', '.kb-amz-cart .cart-heading', function(){
            $(this).closest('.kb-amz-cart').addClass('active');
        });
        
        $(document).mouseup(function (e)
        {
            var container = $('.kb-amz-cart');
            if (!container.is(e.target)
                && container.has(e.target).length === 0) {
                container.removeClass('active');
            }
        });
        
        // Images Height
        $('.kb-amz-products-list').each(function(){
            var maxHeight = 100;
            $(this).find('.kb-amz-item-list-img').each(function(){
                var width = $(this).width();
                var $img = $(this).find('img.kb-amz-first');
                var w = parseInt($img.attr('width'));
                var h = parseInt($img.attr('height'));
                if (h > w) {
                    var height = parseInt(width * (h / w));
                    if (maxHeight < height) {
                        maxHeight = height;
                    }
                }
            }).height(maxHeight);
            $(this).find('.kb-amz-item-list-img').find('img').load(function(){
                var w  = $(this).closest('.kb-amz-item-list-img').width();
                var w1 = $(this).width();
                $(this).css('margin-left', ((w - w1) / 2) + 'px');
            });
        });
  
        $('body').on('click', '.kb-amz-shortcode-product-gallery.has-thumbs .kb-product-thumbs img', function(){
            var $this = $(this);
            var $img = $this.closest('.kb-amz-shortcode-product-gallery').find('.kb-product-image img');
            $img.attr('src', $this.attr('src')).css('opacity', '0.5');
            $img.animate({opacity:1}, 0);
        
        });
        
        // Second Image Switch
        $('body').on('mouseover', '.kb-amz-hover-switch-active', function() {
            $(this).addClass('kb-amz-img-absolute');
            var $im1 = $(this).find('.kb-amz-first');
            var $im2 = $(this).find('.kb-amz-second');
            if ($im2.length > 0) {
                $im1.stop().animate({opacity: 0}, 200);
                $im2.stop().animate({opacity: 1}, 200);  
            }
        });
        
        $('body').on('mouseout', '.kb-amz-hover-switch-active', function() {
            $(this).removeClass('kb-amz-img-absolute');
            var $im1 = $(this).find('.kb-amz-first');
            var $im2 = $(this).find('.kb-amz-second');
            if ($im2.length > 0) {
                $im1.stop().animate({opacity: 1}, 200);
                $im2.stop().animate({opacity: 0}, 200);
            }
        });
        
        // Tabs
        $('.kb-product-tabs.kb-event .kb-product-tabs-contents').first().show();
        $('.kb-product-tabs.kb-event .kb-product-tabs-header').click(function(){
            var index = $(this).index();
            var $contents = $(this).closest('.kb-product-tabs').find('.kb-product-tabs-contents .kb-product-tabs-content');
            $contents.hide();
            $contents.eq(index).show();
        });
    });
})(jQuery);