<?php

namespace EvolutionCMS\ePasskeys\Models;

use EvolutionCMS\ePasskeys\Support\Config;
use EvolutionCMS\ePasskeys\Support\Serializer;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webauthn\PublicKeyCredentialSource;

class Passkey extends Model
{
    protected $guarded = [];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function data(): Attribute
    {
        $serializer = Serializer::make();

        return new Attribute(
            get: fn (string $value) => $serializer->fromJson($value, PublicKeyCredentialSource::class),
            set: fn (PublicKeyCredentialSource $value) => [
                'credential_id' => self::encodeCredentialId($value->publicKeyCredentialId),
                'data' => $serializer->toJson($value),
            ],
        );
    }

    public function authenticatable(): BelongsTo
    {
        $authenticatableModel = Config::getAuthenticatableModel();
        return $this->belongsTo($authenticatableModel, 'authenticatable_id');
    }

    public static function encodeCredentialId(string $raw): string
    {
        return Config::base64UrlEncode($raw);
    }
}
