<?php

namespace App\Core\User;

use App\Core\Formatter\ExceptionMessage\ExceptionMessageStandard;
use App\Core\Query\OrderDirection;
use App\Core\User\Query\UserOrderBy;
use App\Exceptions\Core\User\UserEmailDuplicatedException;
use App\Models\User\Enum\UserExceptionCode;
use App\Models\User\User;
use App\Port\Core\User\CreateUserPort;
use App\Port\Core\User\GetAllUserPort;
use App\Port\Core\User\GetUserPort;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserCore implements UserCoreContract
{
    public function create(CreateUserPort $request): User
    {
        try {
            DB::beginTransaction();

            $isEmailExist = User::query()
                ->where('email', $request->getEmail())
                ->exists();
            if ($isEmailExist) {
                throw new UserEmailDuplicatedException(new ExceptionMessageStandard(
                    'Email is duplicated',
                    UserExceptionCode::DUPLICATED->value,
                ));
            }

            $user = new User;
            $user->name = $request->getName();
            $user->email = $request->getEmail();
            $user->password = Hash::make($request->getUserPassword());
            $user->save();

            DB::commit();

            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function get(GetUserPort $request): User
    {
        return $request->getUserModel();
    }

    public function getAll(GetAllUserPort $request): LengthAwarePaginator
    {
        $page = $request->getPage() ?? 1;
        $perPage = $request->getPerPage() ?? 30;
        $orderDirection = $request->getOrderDirection() ?? OrderDirection::DESCENDING;
        $orderBy = $request->getOrderBy() ?? UserOrderBy::CREATED_AT;

        return User::query()
            ->orderBy($orderBy->value, $orderDirection->value)
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
