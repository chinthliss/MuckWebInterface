<?php

namespace App\Http\Controllers;

use App\Avatar\AvatarGradient;
use App\Avatar\AvatarInstance;
use App\Avatar\AvatarService;
use App\Muck\MuckService;
use App\Muck\MuckObjectService;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Imagick;
use ImagickException;
use JetBrains\PhpStorm\ArrayShape;

class AvatarController extends Controller
{
    /**
     * @throws Exception
     */
    public function showAvatarEditor(AvatarService $service): View
    {
        /** @var User $user */
        $user = auth()->user();
        $character = $user->getCharacter();

        $options = $service->getAvatarOptions($character);

        return view('multiplayer.avatar')->with([
            'gradients' => $options['gradients'],
            'items' => $options['items'],
            'backgrounds' => $options['backgrounds'],
            'avatarWidth' => $service::DOLL_WIDTH,
            'avatarHeight' => $service::DOLL_HEIGHT
        ]);
    }

    /**
     * @param AvatarService $avatarService
     * @return array
     * @throws Exception
     */
    #[ArrayShape([
        'background' => "array|null",
        'items' => "array",
        'colors' => "string[]"
    ])]
    public function getAvatarState(AvatarService $avatarService): array
    {
        /** @var User $user */
        $user = auth()->user();
        $character = $user->getCharacter();
        $avatar = $avatarService->getAvatarInstanceFor($character);

        return [
            'background' => $avatar->background?->toCatalogArray(),
            'items' => array_map(function ($item) {
                return $item->toCatalogArray();
            }, $avatar->items),
            'colors' => $avatar->colors
        ];
    }

    /**
     * Attempts to set the present avatar state
     * @param AvatarService $service
     * @param MuckService $muck
     * @param Request $request
     * @return void
     * @throws Exception
     */
    public function setAvatarState(AvatarService $service, Request $request, MuckService $muck): void
    {
        Log::Debug('Avatar - setAvatarState called with: ' . json_encode($request->all()));

        if (!$request->has('colors') || !$request->has('items') || !$request->has('background'))
            abort(400, 'Missing fields in request.');

        /** @var User $user */
        $user = auth()->user();
        $character = $user->getCharacter();

        //We need to validate things first to make sure they're available and owned/earned.
        $options = $service->getAvatarOptions($character);

        //Colors
        foreach ($request->get('colors') as $slot => $gradientId) {
            if (!$gradientId) continue;
            if (!array_key_exists($gradientId, $options['gradients'])) abort(400, "The gradient '$gradientId' isn't available.");
            $correctedSlot = $slot;
            if ($slot === 'skin1') $correctedSlot = 'fur';
            if ($slot === 'skin2') $correctedSlot = 'fur';
            if ($slot === 'skin3') $correctedSlot = 'skin';
            if (!in_array($correctedSlot, $options['gradients'][$gradientId])) abort(400, "Gradient '$gradientId' isn't available for the color slot '$correctedSlot'.");
        }

        //Background
        $backgroundWanted = $request->get('background');
        if ($backgroundWanted) {
            $backgroundDetails = null;
            foreach ($options['backgrounds'] as $background) {
                if ($background['id'] == $backgroundWanted['id']) $backgroundDetails = $background;
            }
            if (!$backgroundDetails) abort(400, "The requested background '" . $backgroundWanted['name'] . "' wasn't an option.");
            if ($backgroundDetails['cost'] && !$backgroundDetails['earned'] && !$backgroundDetails['owner']) {
                abort(400, "The requested background '" . $backgroundWanted['name'] . "' isn't owned/earned.");
            }
        }

        //Items
        foreach ($request->get('items') as $itemWanted) {
            $itemDetails = null;
            foreach ($options['items'] as $item) {
                if ($item['id'] == $itemWanted['id']) $itemDetails = $item;
            }
            if (!$itemDetails) abort(400, "The requested item '" . $itemWanted['name'] . "' wasn't an option.");
            if ($itemDetails['cost'] && !$itemDetails['earned'] && !$itemDetails['owner']) {
                abort(400, "The requested item '" . $itemWanted['name'] . "' isn't owned/earned.");
            }
        }

        //Pass to muck to save.
        // The colors array is fine, but we need to process just the key details from the items
        $items = $request->get('items') ?? [];
        if ($backgroundWanted) {
            $backgroundWanted['z'] = 0; // This is so the previous system at least draws it behind foreground objects
            $items[] = $backgroundWanted;
        }
        $items = array_map(function ($item) {
            return [
                'id' => $item['id'],
                'x' => $item['x'],
                'y' => $item['y'],
                'z' => $item['z'],
                'rotate' => $item['rotate'],
                'scale' => $item['scale'],
                //The old system needs to know the name of the actual image, which is the id
                //TODO : Remove setting the picture attribute on avatar items after changeover to the new system
                'picture' => $item['id']
            ];
        }, $items);
        $muck->saveAvatarCustomizations(
            $character,
            $request->get('colors'),
            $items
        );
    }

    public function showAdminHub(): View
    {
        return view('admin.avatar-hub');
    }

    public function showAdminDollList(AvatarService $service, MuckService $muckConnection): View
    {
        $dollUsage = $muckConnection->avatarDollUsage();
        // Going to unset entries in dollUsage as they're used, so we can track any remaining.
        $dolls = array_map(/**
         * @throws Exception
         */ function ($doll) use (&$dollUsage, $service) {
            $usage = [];
            if (array_key_exists($doll, $dollUsage)) {
                $usage = $dollUsage[$doll];
                unset($dollUsage[$doll]);
            }
            return [
                'name' => $doll,
                'url' => route('admin.avatar.dollthumbnail', ['dollName' => $doll]),
                'edit' => route('admin.avatar.dolltest', ['code' => $service->getBaseCodeForDoll($doll, true, true)]),
                'usage' => $usage
            ];
        }, $service->getDollNames());

        return view('admin.avatar-doll-list')->with([
            'dolls' => $dolls,
            'invalid' => $dollUsage
        ]);
    }

    /**
     * @throws ImagickException
     * @throws Exception
     */
    public function showAdminDollTest(AvatarService $service, string $code = ''): Mixed
    {
        //Redirect to doll list if a code isn't specified
        if (!$code) return redirect()->route('admin.avatar.dolllist');

        $avatar = AvatarInstance::fromCode($code);
        $drawingSteps = $service->getDrawingPlanForAvatarInstance($avatar);
        //Return simplified version without the doll object
        $drawingSteps = array_map(function ($step) {
            return [
                'dollName' => $step->dollName,
                'part' => $step->part,
                'subPart' => $step->subPart,
                'layers' => $step->layers
            ];
        }, $drawingSteps);

        $dolls = $service->getDollNames();
        $gradients = array_map(function ($gradient) {
            return $gradient->name;
        }, $service->getGradients());

        return view('admin.avatar-doll-test')->with([
            'code' => $code,
            'drawingSteps' => $drawingSteps,
            'dolls' => $dolls,
            'gradients' => $gradients,
            'avatarWidth' => $service::DOLL_WIDTH,
            'avatarHeight' => $service::DOLL_HEIGHT
        ]);
    }

    private function applyPreferencesToAvatarInstance(AvatarInstance $avatarInstance, User $user): void
    {
        // Hidden is treated as clean for instances where it needs to be displayed, such as the editor
        switch ($user->getAvatarPreference()) {
            case $user::AVATAR_PREFERENCE_HIDDEN:
            case $user::AVATAR_PREFERENCE_CLEAN:
                $avatarInstance->mode = AvatarService::MODE_CLEAN;
                break;
            case $user::AVATAR_PREFERENCE_EXPLICIT:
                $avatarInstance->mode = AvatarService::MODE_EXPLICIT;
        }
    }

    /**
     * @throws ImagickException
     */
    private function applyOptionsToAvatarImage(Imagick $avatarImage, Request $request): void
    {
        if ($request->has('mode')) {
            $mode = $request->get('mode');
            if ($mode == 'inline') {
                $avatarImage->cropImage(150, 120, 130, 52);
                $avatarImage->setImagePage(150, 120, 0, 0);
                //$avatarImage->scaleImage(85, 60);
            }
        }
    }

    /**
     * @throws ImagickException
     */
    public function getThumbnailForDoll(AvatarService $service, string $dollName): Response
    {
        $image = $service->getDollThumbnail($dollName);
        return response($image, 200)
            ->header('Content-Type', $image->getImageFormat());
    }

    /**
     * Returns an avatar from a full specification
     * @param AvatarService $service
     * @param string $code
     * @return Response
     * @throws ImagickException
     * @throws Exception
     */
    public function getAvatarFromAdminCode(AvatarService $service, string $code): Response
    {
        $avatar = AvatarInstance::fromCode($code);
        $image = $service->renderAvatarInstance($avatar);
        return response($image, 200)
            ->header('Content-Type', $image->getImageFormat());
    }

    /**
     * For the avatar editor - returns an avatar where the avatar doll is always the user's active character
     * @param AvatarService $service
     * @param string|null $code
     * @return Response
     * @throws ImagickException
     * @throws Exception
     */
    public function getAvatarFromUserCode(AvatarService $service, string $code = null): Response
    {
        /** @var User $user */
        $user = auth()->user();
        $character = $user->getCharacter();
        $avatarInstance = $service->getAvatarInstanceFor($character);
        $this->applyPreferencesToAvatarInstance($avatarInstance, $user);

        //Overwrite colors with any specified by the editor
        $colors = json_decode(base64_decode($code), true);
        $avatarInstance->colors = $colors ?? [];

        //Remove items/background
        $avatarInstance->items = [];
        $avatarInstance->background = null;

        $image = $service->renderAvatarInstance($avatarInstance);
        return response($image, 200)
            ->header('Content-Type', $image->getImageFormat());
    }

    /**
     * @throws ImagickException
     * @throws Exception
     */
    public function getAvatarFromCharacterName(AvatarService $service, MuckObjectService $muckObjectService,
                                               Request       $request, string $name): Response
    {
        /** @var User $user Optional */
        $user = auth()->user();

        if ($user && $user->getAvatarPreference() == User::AVATAR_PREFERENCE_HIDDEN) abort(204);

        if (str_ends_with(strtolower($name), '.png')) $name = substr($name, 0, -4);
        //TODO: Investigate a muckservice function to do this in one muck call
        $character = $muckObjectService->getByPlayerName($name);
        if (!$character) abort(404);
        $avatarInstance = $service->getAvatarInstanceFor($character);
        // Apply any preferences for the present user if there is one
        if ($user) $this->applyPreferencesToAvatarInstance($avatarInstance, $user);
        $image = $service->renderAvatarInstance($avatarInstance);
        $this->applyOptionsToAvatarImage($image, $request);
        return response($image, 200)
            ->header('Content-Type', $image->getImageFormat());
    }

    /**
     * @throws ImagickException
     */
    public function getAllAvatarsAsAGif(AvatarService $service, Request $request): Response
    {
        set_time_limit(500);
        $image = $service->getAnimatedGifOfAllAvatarDolls();
        //Need to apply the options to every frame!
        for ($i = 0; $i < $image->getNumberImages(); $i++) {
            $image->setIteratorIndex($i);
            $this->applyOptionsToAvatarImage($image, $request);
        }
        return response($image->getImagesBlob(), 200)
            ->header('Content-Type', $image->getImageFormat());
    }


    #region Gradients

    public function showUserAvatarGradients(AvatarService $service): View
    {
        $gradients = [];
        foreach ($service->getGradients() as $gradient) {
            $gradients[] = [
                'name' => $gradient->name,
                'desc' => $gradient->desc,
                'free' => $gradient->free,
                'url' => route('avatar.gradient.render', ['name' => $gradient->name])
            ];
        }
        return view('multiplayer.avatar-gradients', [
            'gradients' => $gradients
        ]);
    }

    public function showAdminAvatarGradients(AvatarService $service): View
    {
        $gradients = [];
        foreach ($service->getGradients() as $gradient) {
            $gradients[] = [
                'name' => $gradient->name,
                'desc' => $gradient->desc,
                'free' => $gradient->free,
                'created_at' => $gradient->created_at,
                'owner_aid' => $gradient->owner?->id(),
                'owner_url' => $gradient->owner?->getAdminUrl(),
                'url' => route('avatar.gradient.render', ['name' => $gradient->name])
            ];
        }
        return view('admin.avatar-gradients', [
            'gradients' => $gradients
        ]);
    }

    /**
     * @throws ImagickException
     */
    public function getGradient(string $name, AvatarService $service): Response
    {
        $gradient = $service->getGradient($name);
        if (!$gradient) abort(404);

        $image = $service->renderGradientImage($gradient, true);
        return response($image, 200)
            ->header('Content-Type', $image->getImageFormat());

    }

    /**
     * @throws ImagickException
     * @throws Exception
     */
    public function getGradientPreview(string $code, AvatarService $service): Response
    {
        $config = json_decode(base64_decode($code), JSON_FORCE_OBJECT);

        if (!array_key_exists('steps', $config)) abort(400);

        $steps = $config['steps'];
        $gradient = new AvatarGradient('_temporary', '_temporary', $steps, true, null);
        $image = $service->renderGradientAvatarPreview($gradient);
        return response($image, 200)
            ->header('Content-Type', $image->getImageFormat());
    }

    public function buyGradient(Request $request, AvatarService $avatarService, MuckService $muck): string
    {
        if (!$request->has('gradient')) abort(400, "Gradient not specified.");
        $gradient = $avatarService->getGradient($request->get('gradient'));
        if (!$gradient) abort(400, "No gradient found with the id of:" . $request->get('gradient'));

        if (!$request->has('slot')) abort(400, "Slot not specified.");
        $slot = $request->get('slot');

        /** @var User $user */
        $user = auth()->user();
        if (!$user) abort(403);

        $character = $user->getCharacter();
        if (!$character) abort(400, "A character isn't set.");

        Log::info("Avatar - Gradient Purchase - $user, $character buying $gradient->name for slot $slot.");

        return $muck->buyAvatarGradient($character, $gradient, $slot);
    }
    #endregion Gradients

    #region Items

    /**
     * @throws ImagickException
     */
    public function getAvatarItem(AvatarService $service, string $id): Response
    {
        $item = $service->getAvatarItem($id);

        if (!$item) abort(404, "Unrecognized Avatar Item - $id");
        $image = $service->getAvatarItemImage($item);
        return response($image, 200)
            ->header('Content-Type', $image->getImageFormat());
    }

    /**
     * @throws ImagickException
     */
    public function getAvatarItemPreview(AvatarService $service, string $id): Response
    {
        $item = $service->getAvatarItem($id);

        if (!$item) abort(404, "Unrecognized Avatar Item - $id");
        $image = $service->renderAvatarItemPreview($item);
        return response($image, 200)
            ->header('Content-Type', $image->getImageFormat());
    }

    public function showAdminAvatarItems(AvatarService $service): View
    {
        $items = array_map(function ($item) use ($service) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'type' => $item->type,
                'filename' => $item->filename,
                'requirement' => $item->requirement,
                'created_at' => $item->createdAt,
                'owner' => $item->owner?->toArray('admin'),
                'cost' => $item->cost,
                'x' => $item->x,
                'y' => $item->y,
                'rotate' => $item->rotate,
                'scale' => $item->scale,
                'url' => route('avatar.item.preview', ['id' => $item->id])
            ];
        }, $service->getAvatarItems());

        $usage = $service->getAvatarItemFileUsage();

        return view('admin.avatar-items', [
            'items' => $items,
            'fileUsage' => $usage
        ]);

    }

    public function buyItem(Request $request, AvatarService $avatarService, MuckService $muck): string
    {
        if (!$request->has('item')) abort(400, "Item not specified.");
        $item = $avatarService->getAvatarItem($request->get('item'));
        if (!$item) abort(400, "No item found with the id of:" . $request->get('item'));

        /** @var User $user */
        $user = auth()->user();
        if (!$user) abort(403);

        $character = $user->getCharacter();
        if (!$character) abort(400, "A character isn't set.");

        Log::info("Avatar - Item Purchase - $user, $character buying $item->id.");
        return $muck->buyAvatarItem($character, $item);

    }

    #endregion Items
}
