    <!-- Footer Section Begin -->
    <section class="footer-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <div class="fs-about">
                        <div class="fa-logo">
                            <a href="index.php"><img src="assets/img/logo.png" alt=""></a>
                        </div>
                        <p>Hệ thống quản lý phòng tập gym hiện đại, mang đến trải nghiệm tập luyện tốt nhất cho khách hàng.</p>
                        <div class="fa-social">
                            <a href="#"><i class="fa fa-facebook"></i></a>
                            <a href="#"><i class="fa fa-twitter"></i></a>
                            <a href="#"><i class="fa fa-youtube-play"></i></a>
                            <a href="#"><i class="fa fa-instagram"></i></a>
                            <a href="#"><i class="fa  fa-envelope-o"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-6">
                    <div class="fs-widget">
                        <h4>Liên kết</h4>
                        <ul>
                            <li><a href="about.php">Về chúng tôi</a></li>
                            <li><a href="blog.php">Tin tức</a></li>
                            <li><a href="classes.php">Lớp tập</a></li>
                            <li><a href="contact.php">Liên hệ</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-6">
                    <div class="fs-widget">
                        <h4>Hỗ trợ</h4>
                        <ul>
                            <li><a href="login.php">Đăng nhập</a></li>
                            <li><a href="register.php">Đăng ký</a></li>
                            <li><a href="packages.php">Gói tập</a></li>
                            <li><a href="contact.php">Liên hệ</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="fs-widget">
                        <h4>Mẹo & Hướng dẫn</h4>
                        <div class="fw-recent">
                            <h6><a href="#">Tập thể dục có thể giúp phòng ngừa trầm cảm, lo âu</a></h6>
                            <ul>
                                <li>3 phút đọc</li>
                                <li>20 Bình luận</li>
                            </ul>
                        </div>
                        <div class="fw-recent">
                            <h6><a href="#">Cách tập luyện tốt nhất để giảm mỡ bụng...</a></h6>
                            <ul>
                                <li>3 phút đọc</li>
                                <li>20 Bình luận</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12 text-center">
                    <div class="copyright-text">
                        <p>Copyright &copy;<script>document.write(new Date().getFullYear());</script> Gym Management System</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Footer Section End -->



    <!-- Js Plugins -->
    <script src="assets/js/jquery-3.3.1.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/jquery.magnific-popup.min.js"></script>
    <script src="assets/js/masonry.pkgd.min.js"></script>
    <script src="assets/js/jquery.barfiller.js"></script>
    <script src="assets/js/jquery.slicknav.js"></script>
    <script src="assets/js/owl.carousel.min.js"></script>
    <script src="assets/js/main.js"></script>

<!-- Global Search Panel -->
<style>
#gs-overlay{position:fixed;inset:0;z-index:8999;display:none;}
#gs-panel{
    display:none;position:fixed;top:68px;right:18px;
    width:380px;max-width:calc(100vw - 36px);
    background:#1a1a1a;border-radius:10px;
    box-shadow:0 8px 32px rgba(0,0,0,.55);z-index:9000;
    border:1px solid #333;overflow:hidden;
    animation:gsSlideIn .15s ease;
}
@keyframes gsSlideIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
#gs-input-wrap{
    display:flex;align-items:center;padding:10px 14px;
    border-bottom:1px solid #2a2a2a;
}
#gs-input-wrap .gs-icon{color:#888;font-size:15px;margin-right:10px;flex-shrink:0;}
#gs-input{
    flex:1;background:transparent;border:none;outline:none;
    color:#fff;font-size:15px;padding:4px 0;
}
#gs-input::placeholder{color:#666;}
#gs-close-btn{
    background:none;border:none;color:#666;font-size:20px;
    cursor:pointer;padding:0 4px;line-height:1;
    flex-shrink:0;
}
#gs-close-btn:hover{color:#f36100;}
#gs-results{max-height:380px;overflow-y:auto;padding:6px 0;}
#gs-results::-webkit-scrollbar{width:4px;}
#gs-results::-webkit-scrollbar-track{background:#111;}
#gs-results::-webkit-scrollbar-thumb{background:#444;border-radius:2px;}
.gs-group-label{
    font-size:10px;font-weight:700;letter-spacing:.08em;
    color:#f36100;text-transform:uppercase;
    padding:8px 14px 3px;
}
.gs-item{
    display:flex;align-items:center;gap:10px;
    padding:8px 14px;cursor:pointer;text-decoration:none;
    transition:background .15s;
}
.gs-item:hover,.gs-item.gs-active{background:#222;}
.gs-item-icon{
    width:30px;height:30px;border-radius:50%;
    background:#2a2a2a;display:flex;align-items:center;
    justify-content:center;flex-shrink:0;
    color:#f36100;font-size:13px;
}
.gs-item-text{flex:1;min-width:0;}
.gs-item-name{color:#e0e0e0;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.gs-item-sub{color:#888;font-size:11px;}
.gs-empty,.gs-loading{
    text-align:center;padding:28px 14px;
    color:#666;font-size:13px;
}
.gs-footer{
    border-top:1px solid #2a2a2a;padding:8px 14px;
    text-align:center;
}
.gs-footer a{
    color:#f36100;font-size:12px;text-decoration:none;
}
.gs-footer a:hover{text-decoration:underline;}
</style>

<div id="gs-overlay"></div>
<div id="gs-panel">
    <div id="gs-input-wrap">
        <i class="fa fa-search gs-icon"></i>
        <input type="text" id="gs-input" placeholder="Tìm sản phẩm, gói tập, HLV, dịch vụ..." autocomplete="off">
        <button id="gs-close-btn" title="Đóng">&#x2715;</button>
    </div>
    <div id="gs-results"><div class="gs-empty">Nhập từ khóa để tìm kiếm...</div></div>
    <div class="gs-footer" id="gs-footer" style="display:none;">
        <a href="#" id="gs-view-all">Xem tất cả kết quả trong trang sản phẩm &rarr;</a>
    </div>
</div>

<script>
(function(){
    var toggle  = document.getElementById('global-search-toggle');
    var panel   = document.getElementById('gs-panel');
    var overlay = document.getElementById('gs-overlay');
    var input   = document.getElementById('gs-input');
    var results = document.getElementById('gs-results');
    var footer  = document.getElementById('gs-footer');
    var viewAll = document.getElementById('gs-view-all');
    var closeBtn= document.getElementById('gs-close-btn');
    var debounce, activeIdx = -1, lastItems = [];

    if (!toggle) return;

    function openPanel(){
        panel.style.display = 'block';
        overlay.style.display = 'block';
        setTimeout(function(){ input.focus(); }, 50);
    }
    function closePanel(){
        panel.style.display = 'none';
        overlay.style.display = 'none';
        input.value = '';
        results.innerHTML = '<div class="gs-empty">Nhập từ khóa để tìm kiếm...</div>';
        footer.style.display = 'none';
        activeIdx = -1; lastItems = [];
    }

    toggle.addEventListener('click', function(e){ e.preventDefault(); openPanel(); });
    overlay.addEventListener('click', closePanel);
    closeBtn.addEventListener('click', closePanel);
    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape') closePanel();
        if (panel.style.display !== 'block') return;
        var items = results.querySelectorAll('.gs-item');
        if (!items.length) return;
        if (e.key === 'ArrowDown'){
            e.preventDefault();
            activeIdx = Math.min(activeIdx + 1, items.length - 1);
            highlightItem(items);
        } else if (e.key === 'ArrowUp'){
            e.preventDefault();
            activeIdx = Math.max(activeIdx - 1, -1);
            highlightItem(items);
        } else if (e.key === 'Enter' && activeIdx >= 0){
            e.preventDefault();
            items[activeIdx].click();
        } else if (e.key === 'Enter' && activeIdx < 0){
            var q = input.value.trim();
            if (q) { window.location.href = 'products.php?keyword=' + encodeURIComponent(q); }
        }
    });

    function highlightItem(items){
        Array.prototype.forEach.call(items, function(el, i){
            el.classList.toggle('gs-active', i === activeIdx);
        });
    }

    input.addEventListener('input', function(){
        clearTimeout(debounce);
        var q = this.value.trim();
        if (!q){ results.innerHTML = '<div class="gs-empty">Nhập từ khóa để tìm kiếm...</div>'; footer.style.display='none'; return; }
        results.innerHTML = '<div class="gs-loading"><i class="fa fa-spinner fa-spin"></i> Đang tìm...</div>';
        debounce = setTimeout(function(){ doSearch(q); }, 260);
    });

    function doSearch(q){
        viewAll.href = 'products.php?keyword=' + encodeURIComponent(q);
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'ajax/global-search.php?q=' + encodeURIComponent(q), true);
        xhr.onload = function(){
            if (xhr.status !== 200){ results.innerHTML='<div class="gs-empty">Có lỗi xảy ra.</div>'; return; }
            var res; try{ res = JSON.parse(xhr.responseText); }catch(e){ return; }
            renderResults(res.results || [], q);
        };
        xhr.onerror = function(){ results.innerHTML='<div class="gs-empty">Có lỗi xảy ra.</div>'; };
        xhr.send();
    }

    function renderResults(items, q){
        activeIdx = -1; lastItems = items;
        if (!items.length){
            results.innerHTML = '<div class="gs-empty">Không tìm thấy kết quả cho &ldquo;' + escHtml(q) + '&rdquo;</div>';
            footer.style.display = 'none';
            return;
        }
        var html = '';
        var curGroup = null;
        items.forEach(function(item){
            if (item.group !== curGroup){
                curGroup = item.group;
                html += '<div class="gs-group-label">' + escHtml(item.group) + '</div>';
            }
            html += '<a class="gs-item" href="' + escAttr(item.url) + '">';
            html += '<div class="gs-item-icon"><i class="fa ' + escAttr(item.icon) + '"></i></div>';
            html += '<div class="gs-item-text">';
            html += '<div class="gs-item-name">' + highlightMatch(escHtml(item.name), escHtml(q)) + '</div>';
            html += '<div class="gs-item-sub">' + escHtml(item.sub) + '</div>';
            html += '</div></a>';
        });
        results.innerHTML = html;
        footer.style.display = 'block';
    }

    function highlightMatch(name, q){
        var re = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&') + ')', 'gi');
        return name.replace(re, '<strong style="color:#f36100">$1</strong>');
    }
    function escHtml(s){ var d=document.createElement('div'); d.appendChild(document.createTextNode(s)); return d.innerHTML; }
    function escAttr(s){ return s.replace(/"/g,'&quot;'); }
})();
</script>

</body>

</html>
