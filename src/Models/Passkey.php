<?php

namespace EvolutionCMS\ePasskeys\Models;

use EvolutionCMS\ePasskeys\Support\Config;
use EvolutionCMS\ePasskeys\Support\Serializer;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\Util\CredentialRecordConverter;

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
            get: fn (string $value) => self::normalizeCredentialSource(
                $serializer->fromJson($value, PublicKeyCredentialSource::class)
            ),
            set: function (PublicKeyCredentialSource|CredentialRecord $value) use ($serializer): array {
                $source = self::normalizeCredentialSource($value);

                return [
                    'credential_id' => self::encodeCredentialId($source->publicKeyCredentialId),
                    'data' => $serializer->toJson($source),
                ];
            },
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

    protected static function normalizeCredentialSource(
        PublicKeyCredentialSource|CredentialRecord $value,
    ): PublicKeyCredentialSource {
        if ($value instanceof CredentialRecord) {
            return CredentialRecordConverter::toPublicKeyCredentialSource($value);
        }

        return $value;
    }
}
