document.addEventListener("DOMContentLoaded", function () {
    const API_BASE_URL = 'http://localhost/GreenFood/public'; // URL API của bạn
    const productGrid = document.querySelector(".product-grid"); // Selector cho product grid
    const paginationControls = document.querySelector(".pagination"); // Selector cho pagination

    // Elements cho filter (bạn cần thêm ID hoặc class cho chúng trong HTML)
    const searchInput = document.getElementById("user-search-input"); // Ví dụ ID
    const searchButton = document.getElementById("user-search-button"); // Ví dụ ID
    const categoryFilters = document.querySelectorAll('input[name="category_user"]'); // Ví dụ name

    let currentPage = 1;
    let currentKeyword = '';
    let currentCategory = '';
    // Thêm các biến cho sort nếu cần

    async function fetchProducts(page = 1, keyword = '', category = '') {
        try {
            let apiUrl = `<span class="math-inline">\{API\_BASE\_URL\}/api/products?page\=</span>{page}`;
            if (keyword) apiUrl += `&keyword=${encodeURIComponent(keyword)}`;
            if (category) apiUrl += `&category=${encodeURIComponent(category)}`;
            // Thêm sort parameters nếu có: &sort_by=Price&sort_direction=asc

            const response = await fetch(apiUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json' // Quan trọng
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();

            if (result.success && result.data && result.data.data) {
                renderProducts(result.data.data);
                renderPagination(result.data); // result.data chứa thông tin pagination
                currentPage = result.data.current_page;
            } else {
                console.error("Failed to fetch products:", result.message || "Unknown error");
                if (productGrid) productGrid.innerHTML = `<p>Không tải được dữ liệu sản phẩm.</p>`;
            }
        } catch (error) {
            console.error("Error fetching products:", error);
            if (productGrid) productGrid.innerHTML = `<p>Lỗi khi tải dữ liệu: ${error.message}</p>`;
        }
    }

    function renderProducts(products) {
        if (!productGrid) return;
        productGrid.innerHTML = ""; // Xóa sản phẩm cũ

        if (products.length === 0) {
            productGrid.innerHTML = `<p>Không có sản phẩm nào phù hợp.</p>`;
            return;
        }

        products.forEach(product => {
            const productItem = document.createElement("div");
            productItem.classList.add("product-item");

            // Tính giá hiển thị (giá gốc, giá khuyến mãi)
            const originalPriceFormatted = Number(product.Price).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });
            let displayPrice = originalPriceFormatted;

            // Giả sử model Product có accessor `discounted_price` như bạn đã định nghĩa
            // Và API trả về discounted_price (bạn cần đảm bảo điều này bằng cách thêm vào $appends trong Model hoặc serialize nó)
            // Hoặc bạn tính lại ở client dựa trên Price và Discount (Discount là số thập phân 0.1 cho 10%)
            let discountedPrice = product.Price * (1 - product.Discount); // Nếu Discount là 0.xx
            const discountedPriceFormatted = Number(discountedPrice).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });

            if (product.Discount > 0 && product.Price > discountedPrice) {
                displayPrice = `<span style="text-decoration: line-through; color: #888;">${originalPriceFormatted}</span> ${discountedPriceFormatted}`;
            }

            // Đường dẫn ảnh: API_BASE_URL + '/storage/' + product.Image nếu Image là tên file
            // Hoặc product.Image nếu nó là URL đầy đủ
            const imageUrl = product.Image ? (product.Image.startsWith('http') ? product.Image : `<span class="math-inline">\{API\_BASE\_URL\}/storage/</span>{product.Image}`) : 'assets/img/placeholder.png';

            productItem.innerHTML =
                <a href="Product-detail.html?id=<span class="math-inline">\{product\.ProductID\}"\> <img src\="</span>{imageUrl}" alt="<span class="math-inline">\{product\.ProductName \|\| 'Ảnh sản phẩm'\}"\>
                <div class="product-name">{product.ProductName || 'N/A'}</div>
                <div class="product-price">${displayPrice}</div>
</a>
`;
            productGrid.appendChild(productItem);
        });
    }
