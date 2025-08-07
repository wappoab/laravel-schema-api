<?php

use Illuminate\Support\Facades\File;
use Wappo\LaravelSchemaApi\Commands\GenerateClientResources;

it('generates all the client files into our tmp folder', function () {

    File::cleanDirectory($this->base);

    File::makeDirectory($this->base . '/frontend/models', 0755, true);
    File::makeDirectory($this->base . '/frontend/stores/collections', 0755, true);
    File::makeDirectory($this->base . '/frontend/enums', 0755, true);
    File::makeDirectory($this->base . '/frontend/components/forms', 0755, true);

    $this->artisan(GenerateClientResources::class)
        ->assertExitCode(0);

    foreach (['Category', 'CategoryPost', 'Post', 'Secret', 'User'] as $typeName) {
        // $this->setFixture(__DIR__ . '/Fixtures/models/'.$typeName.'.ts', (string)File::get($this->base . '/frontend/models/'.$typeName.'.ts'));
        expect(File::exists($this->base . '/frontend/models/'.$typeName.'.ts'))->toBeTrue()
            ->and(File::get($this->base . '/frontend/models/'.$typeName.'.ts'))->toEqual(
                $this->getFixture(__DIR__ . '/Fixtures/models/'.$typeName.'.ts'),
            );
        // $this->setFixture(__DIR__ . '/Fixtures/components/forms/'.$typeName.'Form.vue', (string)File::get($this->base . '/frontend/components/forms/'.$typeName.'Form.vue'));
        expect(File::exists($this->base . '/frontend/components/forms/'.$typeName.'Form.vue'))->toBeTrue()
            ->and(File::get($this->base . '/frontend/components/forms/'.$typeName.'Form.vue'))->toEqual(
                $this->getFixture(__DIR__ . '/Fixtures/components/forms/'.$typeName.'Form.vue'),
            );
    }

    //$this->setFixture(__DIR__ . '/Fixtures/enums/CollectionName.ts', (string)File::get($this->base . '/frontend/enums/CollectionName.ts'));
    expect(File::exists($this->base . '/frontend/enums/CollectionName.ts'))->toBeTrue()
        ->and(File::get($this->base . '/frontend/enums/CollectionName.ts'))->toEqual(
            $this->getFixture(__DIR__ . '/Fixtures/enums/CollectionName.ts'),
        );

    // $this->setFixture(__DIR__ . '/Fixtures/stores/collections.ts', (string)File::get($this->base . '/frontend/stores/collections.ts'));
    expect(File::exists($this->base . '/frontend/stores/collections.ts'))->toBeTrue()
        ->and(File::get($this->base . '/frontend/stores/collections.ts'))->toEqual(
            $this->getFixture(__DIR__ . '/Fixtures/stores/collections.ts'),
        );

    // $this->setFixture(__DIR__ . '/Fixtures/components.ts', (string)File::get($this->base . '/frontend/components.ts'));
    expect(File::exists($this->base . '/frontend/components.ts'))->toBeTrue()
        ->and(File::get($this->base . '/frontend/components.ts'))->toEqual(
            $this->getFixture(__DIR__ . '/Fixtures/components.ts'),
        );

    foreach (['categories', 'category_post', 'posts', 'secrets', 'users'] as $typeName) {
        // $this->setFixture(__DIR__ . '/Fixtures/stores/collections/'.$typeName.'.ts', (string)File::get($this->base . '/frontend/stores/collections/'.$typeName.'.ts'));
        expect(File::exists($this->base . '/frontend/stores/collections/'.$typeName.'.ts'))->toBeTrue()
            ->and(File::get($this->base . '/frontend/stores/collections/'.$typeName.'.ts'))->toEqual(
                $this->getFixture(__DIR__ . '/Fixtures/stores/collections/'.$typeName.'.ts'),
            );
    }
});