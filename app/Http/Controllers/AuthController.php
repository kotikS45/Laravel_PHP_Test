<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;
class AuthController extends Controller
{

    /**
     * @OA\Get(
     *   path="/auth/register",
     *   tags={"Auth"},
     *   summary="Show the registration form",
     *   operationId="showRegisterForm",
     *   description="This method returns the registration form view for the user.",
     *   @OA\Response(
     *     response=200,
     *     description="Registration form",
     *     @OA\MediaType(
     *       mediaType="text/html"
     *     )
     *   )
     * )
     */
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    /**
     * @OA\Get(
     *   path="/auth/login",
     *   tags={"Auth"},
     *   summary="Show the login form",
     *   operationId="showLoginForm",
     *   description="This method returns the login form view for the user.",
     *   @OA\Response(
     *     response=200,
     *     description="Login form",
     *     @OA\MediaType(
     *       mediaType="text/html"
     *     )
     *   )
     * )
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * @OA\Get(
     *   path="/auth/google/redirect",
     *   tags={"Auth"},
     *   summary="Redirect to Google OAuth",
     *   operationId="redirectToGoogle",
     *   description="This method initiates Google authentication by redirecting the user to Google for login.",
     *   @OA\Response(
     *     response=302,
     *     description="Redirects the user to Google for authentication"
     *   )
     * )
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * @OA\Post(
     *   path="/register",
     *   tags={"Auth"},
     *   @OA\RequestBody(
     *     required=true,
     *     description="User register data",
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         required={"name","email", "password", "image"},
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="email", type="string"),
     *         @OA\Property(property="password", type="string"),
     *         @OA\Property(property="image", type="file"),
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\MediaType(
     *       mediaType="application/json"
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthenticated"
     *   )
     * )
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'image' => 'file',
        ]);
        if ($request->hasFile('image')) {
            $takeImage = $request->file('image');
            $manager = new ImageManager(new Driver());
            $filename = time();
            $sizes = [100, 300, 500];
            foreach ($sizes as $size) {
                $image = $manager->read($takeImage);
                $image->scale(width: $size, height: $size);
                $image->toWebp()->save(base_path('public/uploads/' . $size . '_' . $filename . '.webp'));
            }
        }
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'image' => $filename . '.webp',
        ]);
        $user->email_verified_at = now();
        $user->save();

        $token = auth()->login($user);
        return response()->json(['token' => $token], Response::HTTP_CREATED);
    }

    /**
     * @OA\Post(
     *   path="/login",
     *   tags={"Auth"},
     *   summary="Login",
     *   operationId="login",
     *   @OA\RequestBody(
     *     required=true,
     *     description="User login data",
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         required={"email", "password"},
     *         @OA\Property(property="email", type="string"),
     *         @OA\Property(property="password", type="string"),
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\MediaType(
     *       mediaType="application/json"
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthenticated"
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Bad Request"
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Not Found"
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="Forbidden"
     *   )
     * )
     */
    public function login(Request $request) {
        $validation = Validator::make($request->all(),[
            'email'=> 'required|email',
            'password'=> 'required|string|min:6'
        ], [
            'email.required' => 'Email is required',
            'email.email' => 'Email is invalid',
            'password.required' => 'Password cannot be empty',
            'password.min' => 'Password must be at least 6 characters',
        ]);
        if($validation->fails()) {
            return response()->json($validation->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if(!$token = auth()->attempt($validation->validated())) {
            return response()->json(['error'=>'Incorrect data!'], Response::HTTP_UNAUTHORIZED);
        }
        return response()->json(['token'=>$token], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *   path="/auth/google/callback",
     *   tags={"Auth"},
     *   summary="Handle Google authentication callback",
     *   operationId="handleGoogleCallback",
     *   description="This method handles the Google authentication callback, logging in or registering the user based on the Google profile data.",
     *   @OA\Response(
     *     response=200,
     *     description="User logged in or registered successfully",
     *     @OA\MediaType(
     *       mediaType="text/html"
     *     )
     *   ),
     *   @OA\Response(
     *     response=302,
     *     description="Redirects the user to the home page after successful authentication"
     *   )
     * )
     */
    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = User::where('email', $googleUser->email)->first();

        if (!$user) {
            $avatarUrl = $googleUser->avatar;
            $takeImage = file_get_contents($avatarUrl);
            $manager = new ImageManager(new Driver());
            $filename = time();
            $sizes = [100, 300, 500];

            foreach ($sizes as $size) {
                $image = $manager->read($takeImage);
                $image->scale(width: $size, height: $size);
                $image->toWebp()->save(base_path('public/uploads/' . $size . '_' . $filename . '.webp'));
            }

            $user = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'password' => Hash::make(Str::random(16)),
                'google_id' => $googleUser->id,
                'image' => $filename . '.webp',
            ]);
            $user->email_verified_at = now();
        }

        Auth::login($user);

        return redirect('/');
    }
}
