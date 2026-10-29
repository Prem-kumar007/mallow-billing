<div class="mb-3">
    <label class="form-label" for="{{ $prefix }}-name">Plan name</label>
    <input id="{{ $prefix }}-name" class="form-control" name="name" value="{{ old('name', $plan->name ?? '') }}" required maxlength="255">
</div>
<div class="row g-2">
    <div class="col-7 mb-3">
        <label class="form-label" for="{{ $prefix }}-price">Base price</label>
        <input id="{{ $prefix }}-price" class="form-control" type="number" name="base_price" value="{{ old('base_price', $plan->base_price ?? '') }}" min="0" step="0.01" required>
    </div>
    <div class="col-5 mb-3">
        <label class="form-label" for="{{ $prefix }}-currency">Currency</label>
        <input id="{{ $prefix }}-currency" class="form-control text-uppercase" name="currency" value="{{ old('currency', $plan->currency ?? 'USD') }}" minlength="3" maxlength="3" pattern="[A-Za-z]{3}" required>
    </div>
</div>
<div class="row g-2">
    <div class="col-6 mb-3">
        <label class="form-label" for="{{ $prefix }}-interval-count">Every</label>
        <input id="{{ $prefix }}-interval-count" class="form-control" type="number" name="billing_interval_count" value="{{ old('billing_interval_count', $plan->billing_interval_count ?? 1) }}" min="1" max="120" required>
    </div>
    <div class="col-6 mb-3">
        <label class="form-label" for="{{ $prefix }}-interval-unit">Interval</label>
        <select id="{{ $prefix }}-interval-unit" class="form-select" name="billing_interval_unit" required>
            @foreach (['day' => 'Day', 'week' => 'Week', 'month' => 'Month', 'year' => 'Year'] as $value => $label)
                <option value="{{ $value }}" @selected(old('billing_interval_unit', $plan->billing_interval_unit ?? 'month') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="mb-3">
    <label class="form-label" for="{{ $prefix }}-allowance">Included units</label>
    <input id="{{ $prefix }}-allowance" class="form-control" type="number" name="included_units" value="{{ old('included_units', $plan->included_units ?? 0) }}" min="0" step="1" required>
</div>
<div class="mb-3">
    <label class="form-label" for="{{ $prefix }}-overage">Overage per unit</label>
    <input id="{{ $prefix }}-overage" class="form-control" type="number" name="overage_rate" value="{{ old('overage_rate', $plan->overage_rate ?? 0) }}" min="0" step="0.0001" required>
</div>
<div class="form-check">
    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="{{ $prefix }}-active" @checked(old('is_active', $active))>
    <label class="form-check-label small" for="{{ $prefix }}-active">Available for new subscriptions</label>
</div>
