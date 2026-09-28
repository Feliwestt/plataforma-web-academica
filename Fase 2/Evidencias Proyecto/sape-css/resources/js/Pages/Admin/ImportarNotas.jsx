import React from 'react';
import { useForm, usePage } from '@inertiajs/react';

export default function ImportarNotas() {
    // Obtenemos las variables de sesión global (flash) desde Inertia
    const { flash } = usePage().props;

    // Manejo del formulario
    const { data, setData, post, processing, errors } = useForm({
        archivo_excel: null,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.importar.store'), {
            forceFormData: true, // Importante para enviar archivos binarios correctamente
        });
    };

    return (
        <div className="p-6 max-w-xl mx-auto bg-white rounded-xl shadow-md space-y-4 mt-10">
            <h1 className="text-xl font-bold text-gray-800">
                Módulo ETL: Importar Nómina y Calificaciones
            </h1>
            
            {/* Mensaje de Éxito */}
            {flash?.success && (
                <div className="p-3 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                    {flash.success}
                </div>
            )}
            
            {/* Mensaje de Error global / Excepción */}
            {flash?.error && (
                <div className="p-3 bg-red-100 border border-red-300 text-red-800 rounded-md text-sm">
                    {flash.error}
                </div>
            )}

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700">
                        Seleccionar Archivo Excel (.xlsx / .csv)
                    </label>
                    <input 
                        type="file" 
                        onChange={e => setData('archivo_excel', e.target.files[0])}
                        className="mt-1 block w-full border border-gray-300 rounded-md p-2 text-sm text-gray-700"
                    />
                    
                    {/* Mensaje de Error de Validación en el archivo */}
                    {errors.archivo_excel && (
                        <div className="text-red-500 text-sm mt-1">
                            {errors.archivo_excel}
                        </div>
                    )}
                </div>

                <button 
                    type="submit" 
                    disabled={processing}
                    className="w-full bg-blue-600 text-white p-2 rounded-md hover:bg-blue-700 transition disabled:opacity-50"
                >
                    {processing ? 'Procesando e importando...' : 'Iniciar Importación Segura'}
                </button>
            </form>
        </div>
    );
}