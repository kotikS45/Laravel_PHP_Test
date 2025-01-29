<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\Response;
class AuthController extends Controller
{
    public function showRegisterForm()
    {
        return view('auth.register');
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
}
