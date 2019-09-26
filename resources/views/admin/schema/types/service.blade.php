<div class="d-flex">
    <a href="{{ route('admin.schema.create', ['type' => $type]) }}" class="stamp stamp-md bg-blue text-white mr-3">
        <i class="fa fa-concierge-bell"></i>
    </a>
    <div>
        <h4 class="m-0">
            <a href="{{ route('admin.schema.create', ['type' => $type]) }}">
                {{ $label ?: 'N/A' }}
            </a>
        </h4>
        <small class="text-muted">
            A service provided by an organization, (e.g. delivery service, print services)
        </small>
    </div>
</div>