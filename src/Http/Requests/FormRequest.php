<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Http\Requests;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FormRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [
            '*.required' => 'Missing required field: :attribute',
            '*.*.required' => 'Missing required field: :attribute',
        ];
    }

    public function queryParamRules(): array
    {
        return [];
    }

    public function queryParamMessages(): array
    {
        return [
            '*.required' => 'Missing required field: :attribute',
            '*.*.required' => 'Missing required field: :attribute',
        ];
    }

    public function queryParamAttributes(): array
    {
        return [];
    }

    /**
     * @throws BindingResolutionException
     */
    public function validateResolved(): void
    {
        $this->validateQueryParams();

        $this->validateFormData();

        parent::validateResolved();
    }

    /**
     * @throws BindingResolutionException
     */
    private function validateQueryParams(): void
    {
        $queryRules = $this->queryParamRules();

        if (count($queryRules) > 0) {
            $this->rejectUnknownKeys(
                array_keys($this->query()),
                array_keys($queryRules),
            );

            /** @var ValidationFactory $factory */
            $factory = $this->container->make(ValidationFactory::class);

            $instance = $factory->make(
                data: $this->query(),
                rules: $queryRules,
                messages: $this->queryParamMessages(),
                attributes: $this->queryParamAttributes(),
            );

            if ($instance->fails()) {
                $this->failedValidation($instance);
            }
        }
    }

    private function validateFormData(): void
    {
        $inputKeys = array_keys(array_diff_key($this->input(), $this->query()));

        if (count($inputKeys) > 0) {
            $this->rejectUnknownKeys($inputKeys, array_keys($this->rules()));
        }
    }

    /**
     * @param list<string> $submitted
     * @param list<string> $allowed
     */
    private function rejectUnknownKeys(array $submitted, array $allowed): void
    {
        $unknown = array_values(array_diff($submitted, $allowed));

        if ($unknown === []) {
            return;
        }

        $message = count($unknown) > 1
            ? sprintf('Received unknown parameters: %s', implode(', ', $unknown))
            : sprintf('Received unknown parameter: %s', $unknown[0]);

        throw new HttpException(
            statusCode: Response::HTTP_BAD_REQUEST,
            message: $message,
        );
    }
}
