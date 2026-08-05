import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import {
    type ColumnDef,
    type RowData,
    type SortingState,
    columnVisibilityFeature,
    createSortedRowModel,
    rowSortingFeature,
    sortFn_alphanumeric,
    sortFn_basic,
    sortFn_datetime,
    sortFn_text,
    tableFeatures,
    useTable,
} from '@tanstack/react-table';
import { useState } from 'react';

/**
 * v9 ships no features by default. `columnVisibilityFeature` backs
 * `row.getVisibleCells()`, and `rowSortingFeature` needs its `sortedRowModel`
 * slot to actually sort. The `sortFns` registry is not optional either: columns
 * default to `sortFn: 'auto'`, and auto-detection resolves the built-in it picks
 * against this registry only — an unregistered name silently falls back to
 * `sortFn_basic`.
 */
const features = tableFeatures({
    columnVisibilityFeature,
    rowSortingFeature,
    sortedRowModel: createSortedRowModel(),
    sortFns: {
        alphanumeric: sortFn_alphanumeric,
        basic: sortFn_basic,
        datetime: sortFn_datetime,
        text: sortFn_text,
    },
});

export type DataTableFeatures = typeof features;

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface DataTableProps<TData extends RowData> {
    columns: ColumnDef<DataTableFeatures, TData>[];
    data: TData[];
    emptyText?: string;
    className?: string;
}

export function DataTable<TData extends RowData>({ columns, data, emptyText = 'No results.', className }: DataTableProps<TData>) {
    const [sorting, setSorting] = useState<SortingState>([]);

    const table = useTable({
        features,
        data,
        columns,
        state: { sorting },
        onSortingChange: setSorting,
    });

    return (
        <div className={cn('rounded-md border', className)}>
            <Table>
                <TableHeader>
                    {table.getHeaderGroups().map((headerGroup) => (
                        <TableRow key={headerGroup.id}>
                            {headerGroup.headers.map((header) => (
                                <TableHead key={header.id}>{header.isPlaceholder ? null : <table.FlexRender header={header} />}</TableHead>
                            ))}
                        </TableRow>
                    ))}
                </TableHeader>
                <TableBody>
                    {table.getRowModel().rows.length ? (
                        table.getRowModel().rows.map((row) => (
                            <TableRow key={row.id}>
                                {row.getVisibleCells().map((cell) => (
                                    <TableCell key={cell.id}>
                                        <table.FlexRender cell={cell} />
                                    </TableCell>
                                ))}
                            </TableRow>
                        ))
                    ) : (
                        <TableRow>
                            <TableCell colSpan={columns.length} className="h-24 text-center">
                                {emptyText}
                            </TableCell>
                        </TableRow>
                    )}
                </TableBody>
            </Table>
        </div>
    );
}

export function DataTablePagination({ links }: { links: PaginationLink[] }) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center justify-center gap-1">
            {links.map((link, index) =>
                link.url ? (
                    <Button key={index} variant={link.active ? 'default' : 'outline'} size="sm" asChild>
                        <Link href={link.url} preserveScroll preserveState>
                            <span dangerouslySetInnerHTML={{ __html: link.label }} />
                        </Link>
                    </Button>
                ) : (
                    <Button key={index} variant="outline" size="sm" disabled>
                        <span dangerouslySetInnerHTML={{ __html: link.label }} />
                    </Button>
                ),
            )}
        </div>
    );
}
