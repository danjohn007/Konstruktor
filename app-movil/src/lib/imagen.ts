import { ImageManipulator, SaveFormat } from 'expo-image-manipulator';
import * as ImagePicker from 'expo-image-picker';
import { Alert } from 'react-native';

/** Lado mayor de las fotos que se suben: suficiente para leer un ticket y ligera para datos móviles. */
const LADO_MAX = 1600;

/** Toma o elige una foto y la reduce a JPEG. Devuelve la uri local o null si se canceló. */
export async function tomarFoto(origen: 'camara' | 'galeria'): Promise<string | null> {
  const permiso =
    origen === 'camara'
      ? await ImagePicker.requestCameraPermissionsAsync()
      : await ImagePicker.requestMediaLibraryPermissionsAsync();
  if (!permiso.granted) {
    Alert.alert('Permiso necesario', origen === 'camara' ? 'Activa el acceso a la cámara en Ajustes.' : 'Activa el acceso a tus fotos en Ajustes.');
    return null;
  }

  const opciones: ImagePicker.ImagePickerOptions = { mediaTypes: ['images'], quality: 1, exif: false };
  const r = origen === 'camara' ? await ImagePicker.launchCameraAsync(opciones) : await ImagePicker.launchImageLibraryAsync(opciones);
  if (r.canceled) return null;

  const foto = r.assets[0];
  const contexto = ImageManipulator.manipulate(foto.uri);
  if (Math.max(foto.width, foto.height) > LADO_MAX) {
    contexto.resize(foto.width >= foto.height ? { width: LADO_MAX, height: null } : { width: null, height: LADO_MAX });
  }
  const imagen = await contexto.renderAsync();
  const guardada = await imagen.saveAsync({ format: SaveFormat.JPEG, compress: 0.7 });
  return guardada.uri;
}

/** Pregunta si usar la cámara o la galería. */
export function elegirFoto(): Promise<string | null> {
  return new Promise((resolve) => {
    Alert.alert('Agregar foto', undefined, [
      { text: 'Tomar foto', onPress: () => tomarFoto('camara').then(resolve) },
      { text: 'Elegir de la galería', onPress: () => tomarFoto('galeria').then(resolve) },
      { text: 'Cancelar', style: 'cancel', onPress: () => resolve(null) },
    ]);
  });
}
