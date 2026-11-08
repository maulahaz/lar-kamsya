<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ExpenseCategory;
use App\Enums\GoatGender;
use App\Enums\GoatOrigin;
use App\Enums\GoatStatus;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use Illuminate\Http\JsonResponse;

class OptionController extends BaseController
{
    /**
     * Reference values used to populate Flutter form controls.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'user_roles' => collect(UserRole::cases())->map(fn (UserRole $role) => [
                    'value' => $role->value,
                    'label' => $role->label(),
                    'description' => $role->description(),
                ])->values(),
                'goat_statuses' => $this->options(GoatStatus::options()),
                'goat_genders' => $this->options(GoatGender::options()),
                'goat_origins' => collect(GoatOrigin::cases())->map(fn (GoatOrigin $origin) => [
                    'value' => $origin->value,
                    'label' => $origin->label(),
                ])->values(),
                'expense_categories' => $this->options(ExpenseCategory::options()),
                'payment_methods' => $this->options(PaymentMethod::options()),
                'report_tabs' => ReportController::TABS,
            ],
        ]);
    }

    /**
     * @param  array<string, string>  $options
     */
    private function options(array $options): array
    {
        return collect($options)
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }
}
