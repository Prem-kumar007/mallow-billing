<div class="nav-section-label mb-2">Workspace</div>
<nav class="nav flex-column mb-4">
    <a class="nav-link-custom {{ $page === 'dashboard' ? 'active' : '' }}"
       data-nav-page="dashboard"
       data-nav-section="dashboard"
       href="{{ route('merchant.dashboard', ['merchantId' => $merchantId]) }}">
        <i class="bi bi-grid-1x2"></i> Dashboard
    </a>
    <a class="nav-link-custom"
       data-nav-page="management"
       data-nav-section="plans"
       href="{{ $page === 'management' ? '#plans' : route('merchant.management', ['merchantId' => $merchantId]).'#plans' }}">
        <i class="bi bi-box-seam"></i> Plans
    </a>
    <a class="nav-link-custom {{ $page === 'management' ? 'active' : '' }}"
       data-nav-page="management"
       data-nav-section="customers"
       href="{{ $page === 'management' ? '#customers' : route('merchant.management', ['merchantId' => $merchantId]).'#customers' }}">
        <i class="bi bi-people"></i> Customers
    </a>
    <a class="nav-link-custom"
       data-nav-page="management"
       data-nav-section="subscriptions"
       href="{{ $page === 'management' ? '#subscriptions' : route('merchant.management', ['merchantId' => $merchantId]).'#subscriptions' }}">
        <i class="bi bi-arrow-repeat"></i> Subscriptions
    </a>
    <a class="nav-link-custom"
       data-nav-page="dashboard"
       data-nav-section="usage"
       href="{{ $page === 'management' ? route('merchant.dashboard', ['merchantId' => $merchantId]).'#usage' : '#usage' }}">
        <i class="bi bi-activity"></i> Usage
    </a>
    <a class="nav-link-custom"
       data-nav-page="dashboard"
       data-nav-section="invoices"
       href="{{ $page === 'management' ? route('merchant.dashboard', ['merchantId' => $merchantId]).'#invoices' : '#invoices' }}">
        <i class="bi bi-receipt"></i> Invoices
    </a>
</nav>

<div class="merchant-card mt-4 p-3 rounded-3">
    <div class="nav-section-label">Current merchant</div>
    <div class="d-flex align-items-center gap-2 mt-3">
        <div class="merchant-avatar">{{ strtoupper(substr($merchant->name, 0, 1)) }}</div>
        <div class="min-w-0">
            <div class="fw-bold text-truncate">{{ $merchant->name }}</div>
            <div class="small text-secondary">Merchant #{{ $merchantId }}</div>
        </div>
    </div>
    <form method="POST" action="{{ route('merchant.logout', ['merchantId' => $merchantId]) }}" class="mt-3">
        @csrf
        <button class="btn btn-sm btn-outline-light" type="submit"><i class="bi bi-box-arrow-right me-1"></i>Sign out</button>
    </form>
</div>

<script>
    const navigationLinks = document.querySelectorAll('.sidebar .nav-link-custom[data-nav-page]');
    const updateActiveNavigation = () => {
        const path = window.location.pathname;
        const currentPage = path.endsWith('/manage')
            ? 'management'
            : 'dashboard';
        const currentSection = path.includes('/invoices/')
            ? 'invoices'
            : window.location.hash.slice(1)
                || (currentPage === 'management' ? 'customers' : 'dashboard');

        navigationLinks.forEach((link) => {
            link.classList.toggle(
                'active',
                link.dataset.navPage === currentPage
                    && link.dataset.navSection === currentSection
            );
        });
    };

    updateActiveNavigation();
    window.addEventListener('hashchange', updateActiveNavigation);
</script>
