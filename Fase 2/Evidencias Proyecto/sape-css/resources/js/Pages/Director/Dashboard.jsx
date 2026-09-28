import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function DirectorDashboard({ auth, userName }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Panel Estratégico - Dirección</h2>}
        >
            <Head title="Director Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-purple-500">
                        <div className="p-6 text-gray-900">
                            <h3 className="text-lg font-bold">¡Bienvenido Director, {userName}!</h3>
                            <p className="mt-2 text-gray-600">Aquí visualizaremos el Simulador de Impacto Institucional y las predicciones de repitencia a largo plazo.</p>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}