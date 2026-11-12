<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use App\Rules\GrantableRole;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateUserRolesRequest extends FormRequest
{
    /**
     * Nobody changes the roles of someone holding a permission they lack.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof User && $actor->outranks($this->target());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'roles' => ['array'],
            'roles.*' => [Rule::exists('roles', 'name'), new GrantableRole($this->user())],
        ];
    }

    /**
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $target = $this->target();

                if ($target->is($this->user())) {
                    $validator->errors()->add('roles', 'You cannot change your own roles from here.');

                    return;
                }

                $roles = $this->input('roles');
                $keepsAdmin = is_array($roles) && in_array(RoleEnum::Admin->value, $roles, true);

                if (! $keepsAdmin && $target->isLastAdministrator()) {
                    $validator->errors()->add('roles', 'The last administrator cannot lose the administrator role.');
                }
            },
        ];
    }

    private function target(): User
    {
        $target = $this->route('user');

        abort_unless($target instanceof User, 404);

        return $target;
    }
}
