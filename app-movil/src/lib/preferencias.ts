import AsyncStorage from '@react-native-async-storage/async-storage';

/** Preferencias del teléfono (no van al servidor). */

const CLAVE_INTRO = 'konstruktor.pref.intro';

/** Si se muestra la presentación al abrir la app. Por defecto, sí. */
export async function verIntro(): Promise<boolean> {
  try {
    return (await AsyncStorage.getItem(CLAVE_INTRO)) !== '0';
  } catch {
    return true;
  }
}

export async function guardarVerIntro(ver: boolean): Promise<void> {
  await AsyncStorage.setItem(CLAVE_INTRO, ver ? '1' : '0');
}
