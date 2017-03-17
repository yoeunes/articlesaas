<?php

namespace App\Http\Controllers\Settings\API;

use Laravel\Spark\Token;
use Laravel\Spark\Spark;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Contracts\Repositories\TokenSecretRepository;
use App\Http\Requests\Settings\API\CreateTokenSecretRequest;
use App\Http\Requests\Settings\API\UpdateTokenSecretRequest;

class TokenSecretController extends Controller
{
    /**
     * The token repository instance.
     *
     * @var TokenRepository
     */
    protected $tokens;

    /**
     * Create a new controller instance.
     *
     * @param  TokenRepository  $tokens
     * @return void
     */
    public function __construct(TokenSecretRepository $tokens)
    {
        $this->tokens = $tokens;

        $this->middleware('auth');
    }

    /**
     * Get all of the tokens generated by the user.
     *
     * @param  Request  $request
     * @return Response
     */
    public function all(Request $request)
    {
        return $this->tokens->all($request->user());
    }

    /**
     * Create a new API token for the user.
     *
     * @param  CreateTokenRequest  $request
     * @return Response
     */
    public function store(CreateTokenSecretRequest $request)
    {
        $data = count(Spark::tokensCan()) > 0 ? ['abilities' => $request->abilities] : [];

        return response()->json(['token' => $this->tokens->createToken(
            $request->user(), $request->name, $request->site, $data
        )->token]);
    }

    /**
     * Update the given API token.
     *
     * @param  UpdateTokenRequest  $request
     * @param  string  $tokenId
     * @return Response
     */
    public function update(UpdateTokenSecretRequest $request, $tokenId)
    {
        $token = $request->user()->tokens()->where('id', $tokenId)->firstOrFail();

        if (class_exists('Laravel\Passport\Passport')) {
            $token = new Token([
                'id' => $token->id,
                'name' => $token->name,
                'metadata' => ['abilities' => $token->scopes],
            ]);
        }

        $this->tokens->updateToken(
            $token, $request->name, (array) $request->abilities
        );
    }

    /**
     * Delete the given token.
     *
     * @param  Request  $request
     * @param  string  $tokenId
     * @return Response
     */
    public function destroy(Request $request, $tokenId)
    {
        $request->user()->tokens()->where('id', $tokenId)->firstOrFail()->delete();
    }
}
