@if ($errors->any())
    <div class="inline-alert inline-alert-warning" style="margin-bottom: 16px;">
        <x-icon name="alert-triangle" :size="16" />
        <span>{{ $errors->first() }}</span>
    </div>
@endif
