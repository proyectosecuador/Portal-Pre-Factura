#!/usr/bin/env python
# -*- coding: utf-8 -*-

"""
Procesador optimizado para Despacho
"""

import pandas as pd
import logging
import os
from datetime import datetime

logger = logging.getLogger(__name__)

class DespachoProcesador:
    """Procesador para archivos de despacho"""
    
    COLUMNAS = {
        'ORDERKEY': {'col': 'C', 'tipo': 'string'},
        'SKU': {'col': 'D', 'tipo': 'string'},
        'STORERKEY': {'col': 'E', 'tipo': 'string'},
        'EXTERNORDERKEY': {'col': 'F', 'tipo': 'string'},
        'SHIPPEDQTY': {'col': 'O', 'tipo': 'float'},
        'UOM': {'col': 'I', 'tipo': 'string'},
        'STATUS': {'col': 'P', 'tipo': 'string'},
        'ADDDATE': {'col': 'CN', 'tipo': 'date'},
    }
    
    HEADERS = ['ORDERKEY', 'SKU', 'STORERKEY', 'EXTERNORDERKEY', 'UNIDADES', 
               'CAJAS', 'PALLETS', 'STATUS', 'ADDDATE']
    
    STATUS_VALIDOS = ['55', '92', '95']
    
    def __init__(self):
        self.logger = logger
    
    def _excel_col_to_index(self, col_letter):
        """Convierte letra de columna a índice"""
        result = 0
        for char in col_letter:
            result = result * 26 + (ord(char.upper()) - ord('A') + 1)
        return result - 1
    
    def _extraer_parte_entera(self, valor):
        """Extrae la parte entera de un número"""
        try:
            if pd.isna(valor):
                return 0
            if isinstance(valor, str):
                import re
                match = re.search(r'(\d+)', valor)
                return int(match.group(1)) if match else 0
            return int(float(valor))
        except:
            return 0
    
    def _convertir_fecha(self, valor):
        """Convierte fecha a formato YYYY-MM-DD"""
        if pd.isna(valor) or valor == '' or valor is None:
            return None
        
        try:
            if isinstance(valor, (datetime, pd.Timestamp)):
                return valor.strftime('%Y-%m-%d')
            
            if isinstance(valor, str):
                valor_str = str(valor).strip()
                
                formatos = [
                    '%d/%m/%y %H:%M',
                    '%d/%m/%Y %H:%M',
                    '%d/%m/%y',
                    '%d/%m/%Y',
                    '%Y-%m-%d',
                    '%Y/%m/%d',
                    '%d-%m-%Y',
                ]
                
                for fmt in formatos:
                    try:
                        fecha = datetime.strptime(valor_str, fmt)
                        return fecha.strftime('%Y-%m-%d')
                    except ValueError:
                        continue
                
                try:
                    fecha = pd.to_datetime(valor_str, dayfirst=True)
                    return fecha.strftime('%Y-%m-%d')
                except:
                    pass
                
                return valor_str
            
            if isinstance(valor, (int, float)):
                try:
                    fecha = pd.Timestamp.fromordinal(int(valor) - 693594)
                    return fecha.strftime('%Y-%m-%d')
                except:
                    pass
            
            return str(valor)
            
        except Exception as e:
            self.logger.error(f"Error convirtiendo fecha {valor}: {e}")
            return None
    
    def _aplicar_filtros_fecha(self, df, col_fecha, fecha_desde, fecha_hasta):
        """Aplica filtros de fecha al dataframe"""
        stats = {
            'filas_filtradas_fecha': 0,
            'fecha_min': None,
            'fecha_max': None
        }
        
        if col_fecha not in df.columns or len(df) == 0:
            return df, stats
        
        print(f"🔍 ANTES DEL FILTRO - Total filas: {len(df)}")
        print(f"🔍 Fechas recibidas - Desde: '{fecha_desde}', Hasta: '{fecha_hasta}'")
        
        # Convertir columna a datetime con dayfirst=True
        try:
            df[col_fecha] = pd.to_datetime(df[col_fecha], errors='coerce', dayfirst=True)
            print(f"✅ Columna convertida a datetime")
        except Exception as e:
            print(f"❌ Error convirtiendo columna: {e}")
            return df, stats
        
        # Mostrar algunas fechas antes del filtro
        fechas_muestra = df[col_fecha].dropna().head(5)
        print(f"🔍 Muestra de fechas en archivo: {list(fechas_muestra)}")
        
        # Guardar estadísticas de fechas originales
        fechas_validas = df[df[col_fecha].notna()]
        if len(fechas_validas) > 0:
            stats['fecha_min'] = fechas_validas[col_fecha].min().strftime('%Y-%m-%d')
            stats['fecha_max'] = fechas_validas[col_fecha].max().strftime('%Y-%m-%d')
            print(f"🔍 Rango original en archivo: {stats['fecha_min']} - {stats['fecha_max']}")
        
        # Aplicar filtros
        df_filtrado = df.copy()
        original_len = len(df_filtrado)
        
        # FILTRO DESDE
        if fecha_desde and fecha_desde != '' and fecha_desde != 'null':
            try:
                # Convertir la fecha desde a datetime
                fecha_desde_dt = pd.to_datetime(fecha_desde, format='%Y-%m-%d')
                print(f"🔍 Aplicando filtro DESDE: {fecha_desde_dt}")
                df_filtrado = df_filtrado[df_filtrado[col_fecha] >= fecha_desde_dt]
                print(f"   Filas después de filtrar DESDE: {len(df_filtrado)}")
            except Exception as e:
                print(f"❌ Error con fecha_desde '{fecha_desde}': {e}")
        
        # FILTRO HASTA
        if fecha_hasta and fecha_hasta != '' and fecha_hasta != 'null':
            try:
                # Convertir la fecha hasta a datetime y agregar 1 día para incluir el día completo
                fecha_hasta_dt = pd.to_datetime(fecha_hasta, format='%Y-%m-%d') + pd.Timedelta(days=1)
                print(f"🔍 Aplicando filtro HASTA: {fecha_hasta_dt}")
                df_filtrado = df_filtrado[df_filtrado[col_fecha] <= fecha_hasta_dt]
                print(f"   Filas después de filtrar HASTA: {len(df_filtrado)}")
            except Exception as e:
                print(f"❌ Error con fecha_hasta '{fecha_hasta}': {e}")
        
        stats['filas_filtradas_fecha'] = original_len - len(df_filtrado)
        print(f"🔍 Total filas filtradas por fecha: {stats['filas_filtradas_fecha']}")
        print(f"🔍 Filas después del filtro: {len(df_filtrado)}")
        
        # Convertir fechas a string para JSON
        df_filtrado[col_fecha] = df_filtrado[col_fecha].apply(
            lambda x: x.strftime('%Y-%m-%d') if pd.notna(x) else ''
        )
        
        return df_filtrado, stats
    
    def _convertir_dataframe_a_lista(self, df):
        """Convierte DataFrame a lista de listas"""
        if df.empty:
            return []
        
        if 'ADDDATE' in df.columns:
            df['ADDDATE'] = df['ADDDATE'].apply(
                lambda x: x if isinstance(x, str) else (x.strftime('%Y-%m-%d') if hasattr(x, 'strftime') else str(x))
            )
        
        return df.fillna('').values.tolist()
    
    def _encontrar_hoja_detail(self, excel_file):
        """Busca la hoja que contiene 'Detail'"""
        sheet_names = excel_file.sheet_names
        
        for name in sheet_names:
            if 'Detail' in name or 'detail' in name:
                return name
        
        return sheet_names[0] if sheet_names else 'Sheet1'
    
    def procesar(self, archivo_path, fecha_desde=None, fecha_hasta=None, request_id=None):
        """Procesa el archivo de despacho"""
        self.logger.info(f"[{request_id}] Procesando archivo de despacho: {archivo_path}")
        print(f"\n{'='*60}")
        print(f"[{request_id}] PROCESANDO DESPACHO")
        print(f"[{request_id}] Archivo: {os.path.basename(archivo_path)}")
        print(f"[{request_id}] Fechas recibidas - Desde: '{fecha_desde}', Hasta: '{fecha_hasta}'")
        print(f"{'='*60}\n")
        
        try:
            # Leer Excel
            excel_file = pd.ExcelFile(archivo_path, engine='openpyxl')
            hoja = self._encontrar_hoja_detail(excel_file)
            
            df = pd.read_excel(archivo_path, sheet_name=hoja, engine='openpyxl', header=None)
            self.logger.info(f"[{request_id}] Hoja: {hoja}, Filas totales: {len(df)}")
            print(f"📊 Hoja encontrada: {hoja}, Filas totales: {len(df)}")
            
            # Extraer columnas específicas
            df_resultado = pd.DataFrame()
            for nombre, info in self.COLUMNAS.items():
                idx = self._excel_col_to_index(info['col'])
                if idx < len(df.columns) and len(df) > 1:
                    df_resultado[nombre] = df.iloc[1:, idx].reset_index(drop=True)
            
            # Limpiar filas vacías
            df_resultado = df_resultado.dropna(subset=['ORDERKEY', 'SKU'], how='all')
            print(f"📊 Después de limpiar vacíos: {len(df_resultado)} filas")
            
            # Filtrar por STATUS
            if 'STATUS' in df_resultado.columns and len(df_resultado) > 0:
                df_resultado['STATUS_STR'] = df_resultado['STATUS'].astype(str).str.strip()
                df_resultado = df_resultado[df_resultado['STATUS_STR'].isin(self.STATUS_VALIDOS)].copy()
                print(f"📊 Después de filtrar STATUS: {len(df_resultado)} filas")
            
            # Procesar cantidades
            if 'SHIPPEDQTY' in df_resultado.columns and len(df_resultado) > 0:
                df_resultado['QTY_ENTERO'] = df_resultado['SHIPPEDQTY'].apply(self._extraer_parte_entera)
            
            # APLICAR FILTROS DE FECHA
            df_resultado, stats_fecha = self._aplicar_filtros_fecha(
                df_resultado, 'ADDDATE', fecha_desde, fecha_hasta
            )
            print(f"📊 Después de filtrar fechas: {len(df_resultado)} filas")
            
            # Agrupar por ORDERKEY + SKU
            if len(df_resultado) > 0:
                df_resultado['GRUPO'] = df_resultado['ORDERKEY'].astype(str) + '_' + df_resultado['SKU'].astype(str)
                
                def agregar_grupo(g):
                    if len(g) == 0:
                        return None
                    primero = g.iloc[0]
                    uom = str(primero['UOM']).strip().upper() if pd.notna(primero['UOM']) else ''
                    cantidad = int(g['QTY_ENTERO'].sum()) if 'QTY_ENTERO' in g.columns else 0
                    
                    return pd.Series({
                        'ORDERKEY': primero['ORDERKEY'],
                        'SKU': primero['SKU'],
                        'STORERKEY': primero['STORERKEY'] if 'STORERKEY' in g.columns else '',
                        'EXTERNORDERKEY': primero['EXTERNORDERKEY'] if 'EXTERNORDERKEY' in g.columns else '',
                        'UNIDADES': cantidad if uom == 'UN' else 0,
                        'CAJAS': cantidad if uom == 'CJ' else 0,
                        'PALLETS': cantidad if uom == 'PL' else 0,
                        'STATUS': primero['STATUS'] if 'STATUS' in g.columns else '',
                        'ADDDATE': g['ADDDATE'].iloc[0] if 'ADDDATE' in g.columns else '',
                    })
                
                df_agrupado = df_resultado.groupby('GRUPO', group_keys=False).apply(agregar_grupo).reset_index(drop=True)
                print(f"📊 Después de agrupar: {len(df_agrupado)} filas")
            else:
                df_agrupado = pd.DataFrame(columns=self.HEADERS)
            
            # Asegurar que las fechas estén en formato string
            if 'ADDDATE' in df_agrupado.columns and len(df_agrupado) > 0:
                df_agrupado['ADDDATE'] = df_agrupado['ADDDATE'].apply(
                    lambda x: x if isinstance(x, str) else (x.strftime('%Y-%m-%d') if hasattr(x, 'strftime') else str(x))
                )
            
            # Calcular estadísticas
            stats = {
                'total_filas': len(df_agrupado),
                'filas_filtradas_fecha': stats_fecha['filas_filtradas_fecha'],
                'fecha_min': stats_fecha['fecha_min'],
                'fecha_max': stats_fecha['fecha_max'],
                'hoja_procesada': hoja,
                'total_unidades': int(df_agrupado['UNIDADES'].sum()) if len(df_agrupado) > 0 else 0,
                'total_cajas': int(df_agrupado['CAJAS'].sum()) if len(df_agrupado) > 0 else 0,
                'total_pallets': int(df_agrupado['PALLETS'].sum()) if len(df_agrupado) > 0 else 0,
                'orderkeys_unicos': df_agrupado['ORDERKEY'].nunique() if len(df_agrupado) > 0 else 0
            }
            
            preview_data = self._convertir_dataframe_a_lista(df_agrupado.head(100))
            full_data = self._convertir_dataframe_a_lista(df_agrupado)
            
            print(f"\n📊 RESUMEN FINAL:")
            print(f"   Total registros: {len(df_agrupado)}")
            print(f"   Unidades: {stats['total_unidades']}")
            print(f"   Cajas: {stats['total_cajas']}")
            print(f"   Pallets: {stats['total_pallets']}")
            print(f"   Rango fechas: {stats['fecha_min']} - {stats['fecha_max']}")
            print(f"{'='*60}\n")
            
            return {
                'success': True,
                'total_registros': len(df_agrupado),
                'headers': self.HEADERS,
                'data': preview_data,
                'data_completa': full_data,
                'stats': stats,
                'metadata': {
                    'archivo_nombre': os.path.basename(archivo_path),
                    'hoja_procesada': hoja,
                    'fechas_aplicadas': {
                        'desde': fecha_desde,
                        'hasta': fecha_hasta
                    }
                }
            }
            
        except Exception as e:
            self.logger.error(f"[{request_id}] Error procesando: {str(e)}", exc_info=True)
            raise