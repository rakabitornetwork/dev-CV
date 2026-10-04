<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Support\CvPayload;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CvDownloadController extends Controller
{
    public function __invoke(): Response|StreamedResponse
    {
        $profile = Profile::query()->firstOrFail();
        $filename = Str::of($profile->name)->ascii()->slug('-')->append('-cv.pdf')->toString();

        if ($this->hasCustomPdf($profile)) {
            return Storage::disk('public')->download($profile->cv_pdf_path, $filename);
        }

        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->setChroot(base_path());

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('cv.pdf', [
            'cv' => CvPayload::landing(),
            'photoPath' => $this->photoPath($profile),
        ])->render());
        $dompdf->setPaper('A4');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function hasCustomPdf(Profile $profile): bool
    {
        if (blank($profile->cv_pdf_path) || $profile->cv_pdf_path === 'cv/amon-pratama.pdf') {
            return false;
        }

        return Storage::disk('public')->exists($profile->cv_pdf_path);
    }

    private function photoPath(Profile $profile): ?string
    {
        if (blank($profile->photo_path)) {
            return null;
        }

        $absolute = Storage::disk('public')->path($profile->photo_path);

        return is_file($absolute) ? $absolute : null;
    }
}
