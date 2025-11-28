<?php
// Nếu không có session thì start
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// BASE URL tuyệt đối (đi từ htdocs)
$BASE = isset($BASE_URL) ? rtrim($BASE_URL, '/') : '';

// Trạng thái login
$isLoggedIn = isset($_SESSION['user_id']);
if (isset($isGuestPage) && $isGuestPage) {
    $isLoggedIn = false;
}
?>

<header class="site-header">
  <div class="container">
    <nav class="nav">
      <!-- Brand -->
      <a href="<?= $BASE ?>/index.php" class="logo">
        🐾 PETCARE
      </a>

      <!-- Main navigation -->
      <ul class="menu">
        <li><a href="<?= $BASE ?>/index.php">Trang chủ</a></li>
        <li><a href="<?= $BASE ?>/introduce.php">Giới thiệu</a></li>
        <li><a href="<?= $BASE ?>/services.php">Dịch vụ</a></li>
        <li><a href="<?= $BASE ?>/doctors.php">Bác sĩ</a></li>
        <li><a href="<?= $BASE ?>/blog-list.php">Tin tức</a></li>
        <li><a href="<?= $BASE ?>/contact.php">Liên hệ</a></li>
      </ul>

      <!-- Actions: search + user -->
      <div class="nav-user-actions">
        <div class="search-wrapper">
          <form action="<?= $BASE ?>/includes/search.php" method="GET" class="search-form">
            <input type="text" name="q" id="search-input" placeholder="Tìm dịch vụ..." aria-label="Nhập từ khóa tìm kiếm" autocomplete="off">
            <button type="submit" class="search-btn">🔍</button>
          </form>
          <div id="search-suggestions" class="search-suggestions"></div>
        </div>

        <?php if ($isLoggedIn): ?>
          <a href="<?= $BASE ?>/user/history.php" class="nav-link-secondary">Lịch sử</a>
          <a href="<?= $BASE ?>/user/profile.php" class="nav-link-secondary">
            Tài khoản
          </a>
          <a href="<?= $BASE ?>/user/logout.php" class="nav-link-secondary" style="color:red;">
            Đăng xuất
          </a>
        <?php else: ?>
          <a href="<?= $BASE ?>/user/login.php" class="btn small">Đăng nhập / Đăng ký</a>
        <?php endif; ?>
      </div>
    </nav>
  </div>
</header>

<style>
.search-wrapper {
    position: relative;
    display: inline-block;
}

.search-suggestions {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    margin-top: 5px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
}

.search-suggestions.show {
    display: block;
}

.suggestion-item {
    padding: 12px 16px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s;
    display: flex;
    align-items: center;
    gap: 10px;
}

.suggestion-item:last-child {
    border-bottom: none;
}

.suggestion-item:hover {
    background-color: #f5f5f5;
}

.suggestion-item.active {
    background-color: #e0f7fa;
}

.suggestion-icon {
    font-size: 18px;
    width: 24px;
    text-align: center;
}

.suggestion-text {
    flex: 1;
}

.suggestion-text strong {
    color: var(--primary-color, #00bcd4);
    font-weight: 600;
}

.suggestion-text small {
    display: block;
    color: #666;
    font-size: 12px;
    margin-top: 2px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.querySelector('.search-form');
    const searchInput = document.getElementById('search-input');
    const suggestionsDiv = document.getElementById('search-suggestions');
    
    // Service suggestions data
    const serviceSuggestions = [
        {
            keywords: ['kham', 'khám', 'chẩn đoán', 'xét nghiệm', 'tổng quát', 'examination', 'diagnosis'],
            title: 'Khám Tổng Quát',
            description: 'Khám sức khỏe định kỳ, chẩn đoán bệnh lý',
            url: '<?= $BASE ?>/service-list/kham.php',
            icon: '🏥'
        },
        {
            keywords: ['tiem', 'tiêm', 'vaccine', 'vaccination', 'phòng', 'phòng bệnh', 'tiêm phòng'],
            title: 'Tiêm Phòng',
            description: 'Vaccine & phòng bệnh cho thú cưng',
            url: '<?= $BASE ?>/service-list/tiem.php',
            icon: '💉'
        },
        {
            keywords: ['phau thuat', 'phẫu thuật', 'surgery', 'phẫu', 'thuật', 'cấp cứu', 'emergency'],
            title: 'Phẫu Thuật',
            description: 'Phẫu thuật & cấp cứu chuyên nghiệp',
            url: '<?= $BASE ?>/service-list/phauthuat.php',
            icon: '⚕️'
        },
        {
            keywords: ['spa', 'grooming', 'tắm', 'cắt tỉa', 'làm đẹp', 'chăm sóc', 'tam', 'cat tia'],
            title: 'Spa & Grooming',
            description: 'Tắm, cắt tỉa, chăm sóc làm đẹp',
            url: '<?= $BASE ?>/service-list/spa.php',
            icon: '🛁'
        },
        {
            keywords: ['hotel', 'lưu trú', 'pet hotel', 'khách sạn', 'luu tru', 'chăm sóc qua đêm'],
            title: 'Pet Hotel',
            description: 'Lưu trú thú cưng an toàn, sạch sẽ',
            url: '<?= $BASE ?>/service-list/hotel.php',
            icon: '🏨'
        },
        {
            keywords: ['shop', 'cửa hàng', 'thức ăn', 'phụ kiện', 'cua hang', 'thuc an', 'phu kien', 'pet shop'],
            title: 'Pet Shop',
            description: 'Thức ăn, phụ kiện cho thú cưng',
            url: '<?= $BASE ?>/service-list/shop.php',
            icon: '🛒'
        }
    ];
    
    let selectedIndex = -1;
    let filteredSuggestions = [];
    
    // Function to filter suggestions based on input
    function filterSuggestions(query) {
        if (!query || query.trim() === '') {
            return [];
        }
        
        const queryLower = query.toLowerCase().trim();
        const matches = [];
        
        serviceSuggestions.forEach(service => {
            // Check if any keyword matches
            const hasMatch = service.keywords.some(keyword => 
                keyword.toLowerCase().includes(queryLower) || 
                queryLower.includes(keyword.toLowerCase()) ||
                service.title.toLowerCase().includes(queryLower)
            );
            
            if (hasMatch) {
                matches.push(service);
            }
        });
        
        return matches;
    }
    
    // Function to highlight matching text
    function highlightMatch(text, query) {
        if (!query) return text;
        const regex = new RegExp(`(${query})`, 'gi');
        return text.replace(regex, '<strong>$1</strong>');
    }
    
    // Function to render suggestions
    function renderSuggestions(suggestions) {
        if (suggestions.length === 0) {
            suggestionsDiv.classList.remove('show');
            return;
        }
        
        suggestionsDiv.innerHTML = '';
        suggestions.forEach((suggestion, index) => {
            const item = document.createElement('div');
            item.className = 'suggestion-item';
            if (index === selectedIndex) {
                item.classList.add('active');
            }
            
            const highlightedTitle = highlightMatch(suggestion.title, searchInput.value);
            
            item.innerHTML = `
                <span class="suggestion-icon">${suggestion.icon}</span>
                <div class="suggestion-text">
                    <div>${highlightedTitle}</div>
                    <small>${suggestion.description}</small>
                </div>
            `;
            
            item.addEventListener('click', function() {
                window.location.href = suggestion.url;
            });
            
            item.addEventListener('mouseenter', function() {
                selectedIndex = index;
                updateActiveItem();
            });
            
            suggestionsDiv.appendChild(item);
        });
        
        suggestionsDiv.classList.add('show');
        filteredSuggestions = suggestions;
    }
    
    // Function to update active item
    function updateActiveItem() {
        const items = suggestionsDiv.querySelectorAll('.suggestion-item');
        items.forEach((item, index) => {
            if (index === selectedIndex) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    }
    
    // Handle input changes
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value;
            const suggestions = filterSuggestions(query);
            selectedIndex = -1;
            renderSuggestions(suggestions);
        });
        
        // Handle keyboard navigation
        searchInput.addEventListener('keydown', function(e) {
            if (!suggestionsDiv.classList.contains('show') || filteredSuggestions.length === 0) {
                return;
            }
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = (selectedIndex + 1) % filteredSuggestions.length;
                updateActiveItem();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = selectedIndex <= 0 ? filteredSuggestions.length - 1 : selectedIndex - 1;
                updateActiveItem();
            } else if (e.key === 'Enter' && selectedIndex >= 0) {
                e.preventDefault();
                window.location.href = filteredSuggestions[selectedIndex].url;
            } else if (e.key === 'Escape') {
                suggestionsDiv.classList.remove('show');
                selectedIndex = -1;
            }
        });
        
        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchForm.contains(e.target)) {
                suggestionsDiv.classList.remove('show');
                selectedIndex = -1;
            }
        });
    }
    
    // Form submission validation
    if (searchForm && searchInput) {
        searchForm.addEventListener('submit', function(event) {
            const keyword = searchInput.value.trim();
            if (keyword === "") {
                event.preventDefault();
                alert("Vui lòng nhập từ khóa tìm kiếm.");
            }
        });
    }
});
</script>