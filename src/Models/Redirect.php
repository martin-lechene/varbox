<?php

namespace Varbox\Models;

use Illuminate\Database\Eloquent\Model;
use Varbox\Contracts\RedirectModelContract;
use Varbox\Exceptions\RedirectException;
use Varbox\Options\ActivityOptions;
use Varbox\Traits\HasActivity;
use Varbox\Traits\IsCacheable;
use Varbox\Traits\IsFilterable;
use Varbox\Traits\IsSortable;

class Redirect extends Model implements RedirectModelContract
{
    use HasActivity;
    use IsCacheable;
    use IsFilterable;
    use IsSortable;

    /**
     * The database table.
     *
     * @var string
     */
    protected $table = 'redirects';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'old_url',
        'new_url',
        'status',
    ];

    /**
     * Boot the model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::saving(function (Redirect $model) {
            if (trim(strtolower($model->old_url), '/') == trim(strtolower($model->new_url), '/')) {
                throw RedirectException::sameUrls();
            }

            static::whereOldUrl($model->new_url)->whereNewUrl($model->old_url)->delete();

            $model->syncOldRedirects($model, $model->new_url);
        });
    }

    /**
     * The mutator to set the "old_url" attribute.
     *
     * @param string $value
     */
    public function setOldUrlAttribute($value)
    {
        $this->attributes['old_url'] = $value === '/' ? $value : trim(parse_url($value)['path'], '/');
    }

    /**
     * The mutator to set the "new_url" attribute.
     *
     * @param string $value
     */
    public function setNewUrlAttribute($value)
    {
        $this->attributes['new_url'] = $value === '/' ? $value : trim(parse_url($value)['path'], '/');
    }

    /**
     * Sync old redirects to point to the new (final) url.
     *
     * @param RedirectModelContract $model
     * @param string $finalUrl
     * @return void
     */
    public function syncOldRedirects(RedirectModelContract $model, $finalUrl)
    {
        $items = static::whereNewUrl($model->old_url)->get();

        foreach ($items as $item) {
            $item->update(['new_url' => $finalUrl]);
            $item->syncOldRedirects($model, $finalUrl);
        }
    }

    /**
     * Return a valid redirect entity for a given path (old url).
     * A redirect is valid if:
     * - it has an url to redirect to (new url)
     * - it's status code is one of the statuses defined on this model
     *
     * @param string $path
     * @return Redirect|null
     */
    public static function findValidOrNull($path)
    {
        return static::where('old_url', $path === '/' ? $path : trim($path, '/'))
            ->whereNotNull('new_url')->where('new_url', '!=', '')
            ->whereIn('status', array_keys((array)config('varbox.redirect.statuses', [])))
            ->latest()->first();
    }

    /**
     * Set the options for the HasActivity trait.
     *
     * @return ActivityOptions
     */
    public function getActivityOptions()
    {
        return ActivityOptions::instance()
            ->withEntityType('redirect')
            ->withEntityName($this->old_url)
            ->withEntityUrl(route('admin.redirects.edit', $this->getKey()));
    }
}