<?php

namespace Flames;

use Flames\Collection\Arr;

class Jwt
{
    protected string|null $privateKey = null;
    protected string|null $publicKey = null;

    public function __construct(string|null $privateKeyPath = null, string|null $publicKeyPath = null)
    {
        if ($privateKeyPath === null) {
            $privateKeyPath = Environment::get('CRYPTO_JWT_PRIVATE');
            if ($privateKeyPath !== null) {
                $this->setPrivateKeyByPath($privateKeyPath);
            }
        } else {
            $this->setPrivateKeyByPath($privateKeyPath);
        }

        if ($publicKeyPath === null) {
            $publicKeyPath = Environment::get('CRYPTO_JWT_PUBLIC');
            if ($publicKeyPath !== null) {
                $this->setPublicKeyByPath($publicKeyPath);
            }
        } else {
            $this->setPublicKeyByPath($publicKeyPath);
        }
    }

    public function setPrivateKeyByPath(string $path): void
    {
        $this->privateKey = file_get_contents(APP_PATH . 'Server/Config/' . $path);
    }

    public function setPublicKeyByPath(string $path): void
    {
        $this->publicKey = file_get_contents(APP_PATH . 'Server/Config/' . $path);
    }

    public function encode(Arr|array $data)
    {
        if ($this->privateKey === null) {
            throw new \Exception('Invalid private key.');
        }

        if ($data instanceof Arr) {
            $data = $data->toArray();
        }

        return \Flames\Jwt\JWT::encode($data, $this->privateKey, 'RS256');
    }

    public function decode(string $token)
    {
        if ($this->publicKey === null) {
            throw new \Exception('Invalid public key.');
        }

        $data = \Flames\Jwt\JWT::decode($token, new \Flames\Jwt\Key($this->publicKey, 'RS256'));
        return Arr((array)$data);
    }
}
