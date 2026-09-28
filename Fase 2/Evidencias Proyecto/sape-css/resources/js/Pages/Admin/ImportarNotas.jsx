import React from 'react';
import { useForm } from '@inertiajs/react';

export default function ImportarNotas({ flash }) {
    // Cambiamos 'archivo' por 'archivo_excel' para que coincida con el controlador
    const { data, setData, post, processing, errors } = useForm({
        archivo_excel: null,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.importar.store'));
    };

    return (
        <div className="p-6 max-w-xl mx-auto bg-white rounded-xl shadow-md space-y-4 mt-10">
            <h1 className="text-xl font-bold text-gray-800">Módulo ETL: Importar Nómina y Calificaciones</h1>
            
            {flash?.success && <div className="p-3 bg-green-100 text-green-700 rounded">{flash.success}</div>}
            
            {/* Capturamos tanto el error general de flash como el error específico del campo */}
            {flash?.error && <div className="p-3 bg-red-100 text-red-700 rounded">{flash.error}</div>}

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700">Seleccionar Archivo Excel (.xlsx / .csv)</label>
                    <input 
                        type="file" 
                        onChange={e => setData('archivo_excel', e.target.files[0])}
                        className="mt-1 block w-full border border-gray-300 rounded-md p-2"
                    />
                    {/* Apuntamos a errors.archivo_excel */}
                    {errors.archivo_excel && <div className="text-red-500 text-sm mt-1">{errors.archivo_excel}</div>}
                </div>

                <button 
                    type="submit" 
                    disabled={processing}
                    className="w-full bg-blue-600 text-white p-2 rounded-md hover:bg-blue-700 transition"
                >
                    {processing ? 'Procesando e importando...' : 'Iniciar Importación Segura'}
                </button>
            </form>
        </div>
    );
}