<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Http\Requests;

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
            $queryKeys = array_keys($this->query());

            $queryRulesKeys = array_keys($queryRules);

            $invalidQueryKeys = array_values(array_diff($queryKeys, $queryRulesKeys));

            if (count($invalidQueryKeys) > 0) {
                $message = count($invalidQueryKeys) > 1
                    ? sprintf(
                        'Received unknown parameters: %s',
                        implode(', ', $invalidQueryKeys),
                    )
                    : sprintf(
                        'Received unknown parameter: %s',
                        $invalidQueryKeys[0],
                    );

                throw new HttpException(
                    statusCode: Response::HTTP_BAD_REQUEST,
                    message: $message,
                );
            }

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
            $rules = $this->rules();

            $rulesKeys = array_keys($rules);

            $invalidInputKeys = array_values(array_diff($inputKeys, $rulesKeys));

            if (count($invalidInputKeys) > 0) {
                $message = count($invalidInputKeys) > 1
                    ? sprintf(
                        'Received unknown parameters: %s',
                        implode(', ', $invalidInputKeys),
                    )
                    : sprintf(
                        'Received unknown parameter: %s',
                        $invalidInputKeys[0],
                    );

                throw new HttpException(
                    statusCode: Response::HTTP_BAD_REQUEST,
                    message: $message,
                );
            }
        }
    }
}
