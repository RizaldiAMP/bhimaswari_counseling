/**
 * Centralized utility to resolve counselor photo URLs.
 * 
 * Priority:
 * 1. photo_url (pre-resolved by backend accessor)
 * 2. photo_path (manually resolved)
 * 3. Fallback: ui-avatars.com API with brand colors
 */

export function getCounselorPhotoUrl(
    photoUrl: string | null | undefined,
    photoPath: string | null | undefined,
    name: string
): string {
    // 1. Use pre-resolved photo_url from backend accessor
    if (photoUrl) {
        return photoUrl;
    }

    // 2. Resolve photo_path manually
    if (photoPath) {
        if (photoPath.startsWith('http') || photoPath.startsWith('/images/')) {
            return photoPath;
        }
        return `/storage/${photoPath}`;
    }

    // 3. Match from local static /images/ path based on counselor's name before falling back to ui-avatars
    const normalized = name.toLowerCase().trim();
    if (normalized.includes('aisyah')) return '/images/aisyah_tri_wardhani.webp';
    if (normalized.includes('bagas')) return '/images/bagas_alam.webp';
    if (normalized.includes('ghazali')) return '/images/ghazali_fauzia.webp';
    if (normalized.includes('ghina')) return '/images/ghina_ciptadewi.webp';
    if (normalized.includes('ifti')) return '/images/ifti_aisha.webp';
    if (normalized.includes('joko')) return '/images/joko_tri_hartanto.webp';
    if (normalized.includes('nadya')) return '/images/nadya_mubaraniz.webp';
    if (normalized.includes('nurul')) return '/images/nurul_nabila_annisa.webp';
    if (normalized.includes('rizkie')) return '/images/rizkie_alief_madani.webp';
    if (normalized.includes('shofura')) return '/images/shofura_hanifah.webp';
    if (normalized.includes('trya')) return '/images/trya_dara_ruidahasi.webp';
    if (normalized.includes('winda')) return '/images/winda_kusuma_ayu.webp';

    // 4. Fallback to ui-avatars
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=E8D3EA&color=823E87&bold=true&size=200`;
}

export function getCounselorFallbackSpecializations(name: string): string[] {
    const normalized = name.toLowerCase().trim();
    if (normalized.includes('aisyah')) return ['Pengembangan Diri', 'Kecemasan'];
    if (normalized.includes('bagas')) return ['Manajemen Stres', 'Depresi'];
    if (normalized.includes('ghazali')) return ['Trauma', 'Kecemasan'];
    if (normalized.includes('ghina')) return ['Pengembangan Diri'];
    if (normalized.includes('ifti')) return ['Keluarga', 'Anak'];
    if (normalized.includes('joko')) return ['Karier', 'Manajemen Stres'];
    if (normalized.includes('nadya')) return ['Kecemasan', 'Keluarga'];
    if (normalized.includes('nurul')) return ['Klinis Dewasa'];
    if (normalized.includes('rizkie')) return ['Pendidikan', 'Remaja'];
    if (normalized.includes('shofura')) return ['Pengembangan Diri'];
    if (normalized.includes('trya')) return ['Tumbuh Kembang'];
    if (normalized.includes('winda')) return ['Psikologi Anak'];
    return ['Pengembangan Diri', 'Manajemen Stres', 'Kecemasan'];
}

export const DEFAULT_COUNSELOR_BIO = 'Praktisi kesehatan mental yang berdedikasi untuk mendampingi perjalanan pemulihan dan pertumbuhan diri Anda melalui sesi konseling yang aman dan suportif.';
